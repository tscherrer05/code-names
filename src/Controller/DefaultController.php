<?php
namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class DefaultController extends AbstractController
{
    private $gameRepository;
    private $playerSession;

    const PLAYER_SESSION_NAME = 'playerId';
    const GAME_SESSION_NAME = 'gameId';

    public function __construct(SessionInterface $playerSession)
    {
        $this->playerSession = $playerSession;
        // TODO : Inject dependencies
        $this->gameRepository = new GameRepository();
    }

    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/game", name="join_game")
     */
    public function game(Request $request)
    {
        $game = $request->query->get('gameId');

        $gameInfo = $this->gameRepository->get(1);
        $board = $gameInfo->board();
        
        $identity = $this->playerSession->get(DefaultController::PLAYER_SESSION_NAME);

        if(!isset($identity))
        {
            return $this->redirectToRoute('user_login',
            [
                'gameId' => $game
            ]);
        }

        // TODO : implement
        // $player = $gameInfo->getPlayer($identity);
        
        $viewModel = [
            "announcedNumber" => $gameInfo->currentNumber(),
            "announcedWord" => $gameInfo->currentWord(),
            "currentTeam" => $gameInfo->currentTeam(),
            "currentPlayerType" => "???",
            "cards" => $board->cards() // TODO : view models for cards
        ];
        return $this->render('default/game.html.twig', $viewModel);
    }

    /**
     * @Route("/login", methods={"GET"}, name="user_login")
     */
    public function login(Request $request)
    {
        $game = $request->query->get('gameId');

        $viewModel = [
            "gameId" => $game
        ];
        return $this->render('default/login.html.twig', $viewModel);
    }

    /**
     * @Route("/login", methods={"POST"})
     */
    public function connect(Request $request)
    {
        $game = $request->query->get('gameId');
        $playerName = $request->query->get('login');
        $role = $request->query->get('role');

        // TODO : save player (game logic)

        // TODO : save session
        $this->playerSession->set(DefaultController::PLAYER_SESSION_NAME, 1);

        return $this->redirectToRoute('join_game', [
            "gameId" => $game
        ]);
    }

    /**
     * @Route("/start")
     */
    public function start()
    {
        return $this->render('default/start.html.twig');
    }

    /**
     * @Route("/disconnect")
     */
    public function disconnect()
    {
        $this->playerSession->remove(DefaultController::PLAYER_SESSION_NAME);
        $this->playerSession->remove(DefaultController::GAME_SESSION_NAME);
    }
}
