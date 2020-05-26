<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Ramsey\Uuid\Guid\Guid;

use App\CodeNames\GameStatus;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;

use App\Tests\CodeNames\TestData as TestData;

class DefaultController extends AbstractController
{
    private $gameRepository;
    private $playerSession;
    private $playerRepository;
    private $gamePlayerRepository;

    const PlayerSession = 'playerKey';
    const GameSession = 'gameKey';

    public function __construct(SessionInterface $session, 
        GameRepository $gameRepo, PlayerRepository $playerRepo,
        GamePlayerRepository $gamePlayerRepository)
    {
        $this->playerSession = $session;
        $this->gameRepository = $gameRepo;
        $this->playerRepository = $playerRepo;
        $this->gamePlayerRepository = $gamePlayerRepository;
    }

    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/game", methods={"GET"}, name="join_game")
     */
    public function game(Request $request)
    {
        $gameKey = $request->query->get('gameKey');
        $identity = $this->playerSession->get(DefaultController::PlayerSession);

        if (!isset($identity)) {
            return $this->redirectToRoute(
                'get_login',
                [
                    'gameKey' => $gameKey
                ]
            );
        }

        try 
        {
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
        } 
        catch (\Exception $e) 
        {
            return $this->render('404.html.twig');
        }

        if($gameInfo->status == GameStatus::Lobby)
        {
            return $this->redirectToRoute('lobby');
        }

        $board = $gameInfo->board();
        $player = $gameInfo->getPlayer($identity);
        $cards = $board->cards();

        $cards = TestData::getCards();

        $viewModel = [
            "gameKey" => $gameKey,
            "announcedNumber" => $gameInfo->currentNumber(),
            "announcedWord" => $gameInfo->currentWord(),
            "currentTeam" => $gameInfo->currentTeam(),
            "currentPlayerKey" => $identity,
            "currentPlayerName" => $player->name,
            "currentPlayerRole" => $player->role,
            "currentPlayerTeam" => $player->team,
            "cards" => $cards, // TODO : view models for cards
            "players" => array_map(function($p) {
                return [
                    'guid' => $p->guid,
                    'name' => $p->name
                ];
            }, $gameInfo->getPlayers())
        ];
        return $this->render('default/game.html.twig', $viewModel);
    }

    /**
     * @Route("/join", methods={"POST"})
     */
    public function join(Request $request)
    {
        $gameKey = $request->request->get('gameKey');
        return $this->redirectToRoute('join_game', [
            "gameKey" => $gameKey
        ]);
    }

    /**
     * @Route("/lobby", methods={"GET"}, name="lobby")
     */
    public function lobby()
    {
        $gameKey = $this->playerSession->get(self::GameSession);
        if($gameKey == null)
        {
            return $this->redirectToRoute('start');
        }

        $identity = $this->playerSession->get(self::PlayerSession);

        if (!isset($identity)) {
            return $this->redirectToRoute(
                'get_login',
                [
                    'gameKey' => $gameKey
                ]
            );
        }

        $gameInfo = $this->gameRepository->getByGuid($gameKey);
        if($gameInfo == null)
        {
            throw new \Exception("Game not found with public key : ". $gameKey);
        }

        if($gameInfo->status == GameStatus::OnGoing)
        {
            return $this->redirectToRoute('game', ['gameKey' => $gameKey]);
        }

        $viewModel = [
            "gameKey" => $gameKey,
            "players" => array_map(function($p) {
                return [
                    'guid' => $p->guid,
                    'name' => $p->name
                ];
            }, $gameInfo->getPlayers()) 
        ];
        return $this->render('default/lobby.html.twig', $viewModel);
    }

    /**
     * @Route("/refreshLobby", methods={"GET"}, name="refreshLobby")
     */
    public function refreshLobby(Request $request)      
    {
        // Pas sûr que cela soit utile
        $this->playerRepository->cleanPlayerSessions();

        return $this->redirectToRoute('lobby');
    }

    /**
     * @Route("/create", methods={"POST"}, name="create_game")
     */
    public function create(Request $request)
    {
        $identity = $this->playerSession->get(self::PlayerSession);
        if (!isset($identity)) 
        {
            return $this->redirectToRoute('get_login');
        }

        $gameInfo = $this->gameFactory->create();
        $this->gameRepository->add($gameInfo);

        return $this->redirectToRoute('lobby', ['gameKey' => $gameInfo->getPublicKey()]);
    }

    /**
     * @Route("/login", methods={"GET"}, name="get_login")
     */
    public function login(Request $request)
    {
        $gameKey = $request->query->get('gameKey');
        $identity = $this->playerSession->get(self::PlayerSession);
        if (isset($identity))
            return $this->redirectToRoute('start');

        $viewModel = [
            'gameKey' => $gameKey
        ];
        return $this->render('default/login.html.twig', $viewModel);
    }

    /**
     * @Route("/login", methods={"POST"}, name="post_login")
     */
    public function connect(Request $request)
    {
        // Parse input
        $playerName = $request->request->get('login');
        $gameKey = $request->request->get('gameKey');

        // Business logic (TODO : Move to business object)
        $game = $this->gameRepository->findByGuid($gameKey);
        if($game == null)
        {
            throw new \Exception("Game not found with key : ".$gameKey);
        }
        $playerKey = Guid::uuid1();

        // Save pdo session (save in database)
        $this->playerSession->set(self::PlayerSession, $playerKey);
        $this->playerSession->set(self::GameSession, $gameKey);

        $player = new Player();
        $player->setName($playerName);
        $player->setPlayerKey($playerKey);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setPlayer($player);
        $gamePlayer->setSessionId($this->playerSession->getId());
        
        // Persistance
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($player);
        $entityManager->persist($gamePlayer);
        $entityManager->flush();


        // Routing
        return $this->redirectToRoute('lobby');
    }

    /**
     * @Route("/start", methods={"GET"}, name="start")
     */
    public function start()
    {
        $identity = $this->playerSession->get(self::PlayerSession);
        $playerName = 'anonyme';

        if(isset($identity))
        {
            $player = $this->playerRepository->findByGuid($identity);
            if(!isset($player))
            {
                return $this->redirectToRoute('user_disconnect');
            }
            $playerName = $player->getName();
        }

        $viewModel = [
            'playerName' => $playerName
        ];
        return $this->render('default/start.html.twig', $viewModel);
    }

    /**
     * @Route("/disconnect", methods={"GET"}, name="user_disconnect")
     */
    public function disconnect()
    {
        $this->playerSession->remove(self::PlayerSession);
        $this->playerSession->remove(self::GameSession);
        return $this->redirectToRoute('start');
    }
}
