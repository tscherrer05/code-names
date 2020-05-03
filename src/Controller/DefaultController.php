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
        $gameId = $request->query->get('id');

        $gameInfo = $this->gameRepository->get($gameId);
        $board = $gameInfo->board();
        
        $identity = $this->playerSession->get(DefaultController::PLAYER_SESSION_NAME);

        if(!isset($identity))
        {
            return $this->redirectToRoute('user_login',
            [
                'gameId' => $gameId
            ]);
        }

        $player = $gameInfo->getPlayer($identity);
        
        $viewModel = [
            "gameId" => $gameId,
            "playerId" => $identity,
            "announcedNumber" => $gameInfo->currentNumber(),
            "announcedWord" => $gameInfo->currentWord(),
            "currentTeam" => $gameInfo->currentTeam(),
            "currentPlayerType" => $player->name,
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
        $game = $request->request->get('gameId');
        $playerName = $request->request->get('login');
        $team = $request->request->get('team');
        $role = $request->request->get('role');

        //save player
        $newPlayerId = $this->gameRepository->addPlayer($game, $playerName, $team, $role);

        // save session
        $this->playerSession->set(DefaultController::PLAYER_SESSION_NAME, $newPlayerId);
        $this->playerSession->set(DefaultController::GAME_SESSION_NAME, $game);

        return $this->redirectToRoute('join_game', [
            "id" => $game
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
     * @Route("/join", methods={"POST"})
     */
    public function join(Request $request)
    {
        $game = $request->request->get('gameId');
        return $this->redirectToRoute('join_game', [
            "id" => $game
        ]);
    }

    /**
     * @Route("/disconnect")
     */
    public function disconnect()
    {
        $this->playerSession->remove(DefaultController::PLAYER_SESSION_NAME);
        $this->playerSession->remove(DefaultController::GAME_SESSION_NAME);
        return $this->render('default/start.html.twig');
    }
}
