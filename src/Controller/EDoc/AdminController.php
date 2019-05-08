<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller\EDoc;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use App\Entity\Archiver;
use App\Service\EdocService;
use Doctrine\ORM\EntityRepository;
use ItkDev\Edoc\Util\ItemListType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ItemListController.
 *
 * @Route(path="/admin/edoc")
 */
class AdminController extends EasyAdminController
{
    /**
     * @Route(path = "/item-list", name = "edoc_item_list")
     */
    public function itemList(Request $request, EdocService $edoc)
    {
        $types = ItemListType::getValues();
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
            ->add('type', ChoiceType::class, [
                'choices' => array_combine(array_values($types), array_values($types)),
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Show',
            ])
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        $type = null;
        $archiver = null;
        $items = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->get('type')->getData();
            $archiver = $form->get('archiver')->getData();
            $edoc->setArchiver($archiver);
            $items = $edoc->getItemList($type);
        }

        return $this->render('admin/edoc/item-list.html.twig', [
            'form' => $form->createView(),
            'archiver' => $archiver,
            'type' => $type,
            'items' => $items,
        ]);
    }
}
