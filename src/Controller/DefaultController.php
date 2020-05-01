<?php
namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;

class DefaultController extends AbstractController
{
    private $gameRepository;

    public function __construct()
    {
        $this->gameRepository = new GameRepository();
    }

    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/game")
     */
    public function game()
    {
        // TODO : devrait être dans un controller côté web

        $gameInfo = $this->gameRepository->get(1);
        $board = $gameInfo->board();

        $result = array(
            "gameInfo" => $gameInfo
        );

        // if(empty($_SESSION["identity"]))
        //     $_SESSION["identity"] = 1;
        // $identity = $_SESSION["identity"];

        // $player = $controller->getPlayer($identity);

        return $this->render('default/game.html.twig');
    }

    public function start()
    {
        return $this->render('default/start.html.twig');
    }
}
