<?php
namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    private $gameRepository;

    public function __construct()
    {
        // TODO : Inject dependencies
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
        $gameInfo = $this->gameRepository->get(1);
        $board = $gameInfo->board();
        
        // TODO : player identity management
        // if(empty($_SESSION["identity"]))
        //     $_SESSION["identity"] = 1;
        // $identity = $_SESSION["identity"];
        // $player = $controller->getPlayer($identity);
        
        $viewModel = [
            "announcedNumber" => $gameInfo->currentNumber(),
            "announcedWord" => $gameInfo->currentWord(),
            "currentTeam" => $gameInfo->currentTeam(),
            "currentPlayerType" => "???",
            "cards" => $board->cards()
        ];
        return $this->render('default/game.html.twig', $viewModel);
    }

    /**
     * @Route("/login")
     */
    public function login(Request $request)
    {
        $game = $request->query->get('game');

        $viewModel = [
            "gameId" => $game
        ];
        return $this->render('default/login.html.twig', $viewModel);
    }

    /**
     * @Route("/start")
     */
    public function start()
    {
        return $this->render('default/start.html.twig');
    }
}
