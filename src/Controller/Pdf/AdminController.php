<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller\Pdf;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use App\Entity\Archiver;
use App\Service\ShareFileService;
use App\ShareFile\Item;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ItemListController.
 *
 * @Route(path="/admin/pdf")
 */
class AdminController extends EasyAdminController
{
    /**
     * @Route(path = "/combine/archiver", name = "pdf_combine_step_0")
     * @Method("GET")
     */
    public function archiver(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('archiver', EntityType::class, [
                'class' => Archiver::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')
                        ->orderBy('a.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Show hearings',
            ])
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $archiver = $form->get('archiver')->getData();

            return $this->redirectToRoute('pdf_combine_step_1', ['archiver' => $archiver->getId()]);
        }

        return $this->render('admin/pdf/combine_step_0.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path = "/combine/archiver/{archiver}/hearing", name = "pdf_combine_step_1")
     * @Method("GET")
     */
    public function hearings(Request $request, Archiver $archiver, ShareFileService $shareFileService)
    {
        $shareFileService->setArchiver($archiver);
        $hearings = $shareFileService->getHearings();
        $choices = [];
        foreach ($hearings as $hearing) {
            $choices[$this->getHearingTitle($hearing)] = $hearing->id;
        }

        $form = $this->createFormBuilder()
            ->add('hearing', ChoiceType::class, [
                'choices' => $choices,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Show responses',
            ])
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hearing = $form->get('hearing')->getData();

            return $this->redirectToRoute('pdf_combine_step_2', [
                'archiver' => $archiver->getId(),
                'hearing' => $hearing,
            ]);
        }

        return $this->render('admin/pdf/combine_step_1.html.twig', [
            'archiver' => $archiver,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path = "/combine/archiver/{archiver}/hearing/{hearing}/response", name = "pdf_combine_step_2")
     * @Method({"GET", "POST"})
     */
    public function files(Request $request, Archiver $archiver, string $hearing, ShareFileService $shareFileService)
    {
        $shareFileService->setArchiver($archiver);
        $hearing = $shareFileService->getItem($hearing);
        $responses = $this->getResponses($shareFileService, $hearing);

        $choices = [];
        foreach ($responses as $response) {
            $choices[$this->getResponseTitle($response)] = $response->id;
        }

        $form = $this->createFormBuilder()
            ->add('responses', ChoiceType::class, [
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                // Select all.
                'data' => array_values($choices),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Generate',
            ])
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && 'POST' === $request->getMethod()) {
            $responses = $form->get('responses')->getData();

            return $this->generate($archiver, $hearing, $responses, $shareFileService);
        }

        return $this->render('admin/pdf/combine_step_2.html.twig', [
            'archiver' => $archiver,
            'hearing' => $hearing,
            'responses' => $responses,
            'form' => $form->createView(),
        ]);
    }

    private function generate(Archiver $archiver, Item $hearing, array $selectedReponseIds, ShareFileService $shareFileService)
    {
        // @TODO Move this to a service and possibly a job queue.
        $shareFileService->setArchiver($archiver);
        $hearing = $shareFileService->getItem($hearing);
        $allResponses = $this->getResponses($shareFileService, $hearing);

        $responses = [];
        $files = [];
        $fileNamePattern = '*-offentlig*.pdf';

        foreach ($allResponses as $response) {
            if (\in_array($response->getId(), $selectedReponseIds, true)) {
                $responses[$response->getId()] = $response;
                $responseFiles = $shareFileService->getFiles($response);
                $responseFiles = array_filter($responseFiles, function (Item $file) use ($fileNamePattern) {
                    return fnmatch($fileNamePattern, $file->name);
                });
                $files[$response->getId()] = reset($responseFiles);
            }
        }

        return new JsonResponse([
            'archiver' => $archiver,
            'hearing' => $hearing,
            'responses' => $responses,
            'files' => $files,
        ]);
    }

    private function getHearingTitle(Item $hearing)
    {
        $title = $hearing->name;

        return $title;
    }

    private function getResponseTitle(Item $response)
    {
        $title = $response->name;

        if (isset($response->metadata['ticket_data']['date_created'])) {
            try {
                $time = new \DateTime($response->metadata['ticket_data']['date_created'], new \DateTimeZone('UTC'));
                $title .= ' ['.$time->format('d/m/Y H:i').']';
            } catch (\Exception $ex) {
            }
        }

        if (isset($response->metadata['user_data']['name'])) {
            $title .= ': '.$response->metadata['user_data']['name'];
        }

        if (isset($response->metadata['ticket_data']['on_behalf_organization'])) {
            $title .= ' ('.$response->metadata['ticket_data']['on_behalf_organization'].')';
        }

        return $title;
    }

    private function getResponses(ShareFileService $shareFileService, Item $hearing, $includeFiles = false)
    {
        // @TODO Handle $includeFiles.

        $responses = $shareFileService->getResponses($hearing);

        // Split into organizations and persons.
        $organizations = array_filter($responses, function (Item $item) {
            return isset($item->metadata['ticket_data']['on_behalf_organization']);
        });
        $persons = array_filter($responses, function (Item $item) {
            return !isset($item->metadata['ticket_data']['on_behalf_organization']);
        });
        // Sort by creation time.
        usort($organizations, function (Item $a, Item $b) {
            return strcmp($a->creationDate, $b->creationDate);
        });
        usort($persons, function (Item $a, Item $b) {
            return strcmp($a->creationDate, $b->creationDate);
        });

        // Combine the result.
        return array_merge($organizations, $persons);
    }
}
