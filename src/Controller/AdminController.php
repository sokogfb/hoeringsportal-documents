<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use AlterPHP\EasyAdminExtensionBundle\Controller\EasyAdminController;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends EasyAdminController
{
    protected function initialize(Request $request)
    {
        parent::initialize($request);
        $twig = $this->get('twig');
        $twig->getExtension('Twig_Extension_Core')->setTimezone($this->getParameter('view_timezone'));
    }
}
