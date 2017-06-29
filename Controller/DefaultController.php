<?php

namespace lmatte7\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('lmatte7PlatformBundle:Default:index.html.twig');
    }
}
