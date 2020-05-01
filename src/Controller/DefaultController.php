<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class DefaultController extends AbstractController
{
    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/game")
     */
    public function game()
    {
        return $this->render('default/game.html.twig');
    }

    public function start()
    {
        return $this->render('default/start.html.twig');
    }
}
