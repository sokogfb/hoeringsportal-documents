<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use App\Entity\ExceptionLogEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends EasyAdminController
{
    /**
     * @Route(path = "/admin/exception_log_entry/hide", name = "exception_log_entry_hide")
     */
    public function hideExceptionLogEntryAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->query->get('id');
        $entity = $entityManager->getRepository(ExceptionLogEntry::class)->find($id);
        if (null !== $entity) {
            $entity->setHidden(true);
            $entityManager->persist($entity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $request->query->get('entity'),
        ]);
    }

    protected function initialize(Request $request)
    {
        parent::initialize($request);
        $twig = $this->get('twig');
        $twig->getExtension('Twig_Extension_Core')->setTimezone($this->getParameter('view_timezone'));
    }
}
