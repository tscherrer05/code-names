<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Ramsey\Uuid\Guid\Guid;
use App\CodeNames\GameStatus;
use App\Entity\Card;
use App\Entity\Colors;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Entity\Teams;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Service\Random;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Annotation\Route;


class DefaultController extends AbstractController
{
    private GameRepository $gameRepository;
    private SessionInterface $session;
    private GamePlayerRepository $gamePlayerRepository;
    private Random $random;

    const PlayerSession = 'playerKey';
    const GameSession = 'gameKey';

    public function __construct(SessionInterface $session,
        GameRepository $gameRepo,
        GamePlayerRepository $gamePlayerRepository,
        Random $random)
    {
        $this->session              = $session;
        $this->gameRepository       = $gameRepo;
        $this->gamePlayerRepository = $gamePlayerRepository;
        $this->random               = $random;
    }

    /**
     * @Route("/getPlayer", methods={"GET"}, name="get_player")
     */
    public function getPlayer() 
    {
        var_dump($this->session->get(self::PlayerSession));
        exit;
    }

    /**
     * @Route("/setPlayer", methods={"GET"}, name="set_player")
     */
    public function setPlayer()
    {
        $this->session->set(self::PlayerSession, $this->getGUID());
        exit;
    }

    /**
     * @Route("/getGame", methods={"GET"}, name="get_game")
     */
    public function getGame() 
    {
        var_dump($this->session->get(self::GameSession));
        exit;
    }

    /**
     * @Route("/setGame", methods={"GET"}, name="set_game")
     */
    public function setGame()
    {
        $this->session->set(self::GameSession, $this->getGUID());
        exit;
    }

    /**
     * 
     */
    public function index()
    {
        return $this->redirectToRoute('start');
    }

    /**
     * @Route("/createGame", methods={"GET"}, name="create_game")
     */
    public function createGame(Request $request)
    {
        $gameKey = $this->session->get(self::GameSession);
        if($gameKey !== null) 
        {
            return $this->redirectToRoute('already_in_game');
        }

        $game = new Game();
        $gameKey = Uuid::uuid1()->toString();
        $game->setPublicKey($gameKey);
        $game->setStatus(GameStatus::OnGoing);
        $game->setCurrentTeam(Teams::Blue);

        $numbers = [
            [Colors::Blue, 9],
            [Colors::Red, 8],
            [Colors::White, 7],
            [Colors::Black, 1],
        ];

        $rows = 5;
        $cols = 5;
        $excludedWords = [];
        for($i = 0; $i <= $cols - 1; $i++) {
            for($j = 0; $j <= $rows - 1; $j++) {
                $card = new Card();
                $card->setX($i);
                $card->setY($j);
                $word = $this->random->word();
                $excludedWords[] = $word;
                $card->setWord($word);
                // Choose color
                $index = $this->random->rand(0, \count($numbers)-1);
                $choice = $numbers[$index];
                $color = $choice[0];
                $number = $choice[1];
                $card->setColor($color);
                if($number === 1) {
                    array_splice($numbers, $index, 1);
                } else {
                    $numbers[$index][1]--;
                }
                $card->setReturned(false);
                $card->setGame($game);
                $em = $this->getDoctrine()->getManager();
                $em->persist($card);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();

        return $this->redirectToRoute('auto_connect', [
            "gameKey" => $gameKey
        ]);
    }

    /**
     * @Route("/game", methods={"GET"}, name="join_game")
     */
    public function game(Request $request)
    {
        // Parsage des paramètres
        $gameKey = $request->query->get('gameKey');
        $playerKey = $this->session->get(self::PlayerSession);

        // Routing
        if(!isset($gameKey) || !isset($playerKey))
        {
            return $this->redirectToRoute('start');
        }

        // Lecture IO
        $gameEntity = $this->gameRepository->findByGuid($gameKey);

        // Routing
        if($gameEntity->getStatus() == GameStatus::Lobby)
        {
            return $this->redirectToRoute('lobby', ['gameKey' => $gameKey]);
        }

        // Lecture IO
        $currPlayer = $this->gamePlayerRepository->findByGuid($playerKey);
        if($currPlayer == null)
        {
            throw new \Exception("Player not found with guid : $playerKey");
        }

        $viewModel = [
            "gameKey"           => $gameKey,
            "announcedNumber"   => $gameEntity->getCurrentNumber(),
            "announcedWord"     => $gameEntity->getCurrentWord(),
            "currentTeam"       => $gameEntity->getCurrentTeam(),
            "currentPlayerKey"  => $playerKey,
            "currentPlayerName" => $currPlayer->getName(),
            "currentPlayerRole" => $currPlayer->getRole(),
            "currentPlayerTeam" => $currPlayer->getTeam()
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
     * @Route("/joinAutoConnect", methods={"POST"})
     */
    public function joinAutoConnect(Request $request)
    {
        $gameKey = $request->request->get('gameKey');
        
        $sessionGameKey = $this->session->get(self::GameSession);
        if(isset($sessionGameKey) && $gameKey !== $sessionGameKey)
        {
            return $this->redirectToRoute('already_in_game');
        }
        
        $game = $this->gameRepository->findByGuid($gameKey);
        if($game !== null)
        {
            return $this->redirectToRoute('auto_connect', ['gameKey' => $gameKey]);
        }

        return $this->startWithError('Clé invalide');
    }

    private function startWithError($errorMessage)
    {
        $identity = $this->session->get(self::PlayerSession);
        $currentGameKey = $this->session->get(self::GameSession);
        $playerName = 'anonyme';
        $isInGame = false;

        if(isset($identity))
        {
            $isInGame = true;
            $player = $this->gamePlayerRepository->findByGuid($identity);
            if(!isset($player))
            {
                return $this->redirectToRoute('user_disconnect');
            }
            $playerName = $player->getName();
        }

        $viewModel = [
            'playerName' => $playerName,
            'isInGame'   => $isInGame,
            'currentGameKey' => $currentGameKey,
            'errorMessage' => $errorMessage
        ];
        return $this->render('default/start.html.twig', $viewModel);
    }

    /**
     * @Route("/alreadyInGame", methods={"GET"}, name="already_in_game")
     */
    public function alreadyInGame()
    {
        $sessionGameKey = $this->session->get(self::GameSession);

        $viewModel = [
            'gameKey' => $sessionGameKey
        ];
        return $this->render('default/alreadyInGame.html.twig', $viewModel);
    }

    /**
     * @Route("/lobby", methods={"GET"}, name="lobby")
     */
    public function lobby()
    {
        $gameKey = $this->session->get(self::GameSession);
        if($gameKey == null)
        {
            return $this->redirectToRoute('start');
        }

        $identity = $this->session->get(self::PlayerSession);

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
            return $this->redirectToRoute('join_game', ['gameKey' => $gameKey]);
        }

        $viewModel = [
            "gameKey" => $gameKey,
            "playerKey" => $identity,
            "players" => array_map(function($p) {
                return [
                    'guid' => $p->guid,
                    'name' => $p->name
                ];
            }, $gameInfo->getPlayers()),
        ];
        return $this->render('default/lobby.html.twig', $viewModel);
    }

    /**
     * @Route("/refreshLobby", methods={"GET"}, name="refreshLobby")
     */
    public function refreshLobby()
    {
        // Pas sûr que cela soit utile
        $this->gamePlayerRepository->cleanPlayerSessions();

        return $this->redirectToRoute('lobby');
    }

    /**
     * @Route("/login", methods={"GET"}, name="get_login")
     */
    public function login(Request $request)
    {
        $gameKey = $request->query->get('gameKey');
        $identity = $this->session->get(self::PlayerSession);
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
        $playerKey = Guid::uuid4()->toString();

        // Save pdo session (save in database)
        $this->session->set(self::PlayerSession, $playerKey);
        $this->session->set(self::GameSession, $gameKey);
        
        $gamePlayer = new GamePlayer();
        $gamePlayer->setName($playerName);
        $gamePlayer->setPublicKey($playerKey);
        $gamePlayer->setGame($game);
        $gamePlayer->setSessionId($this->session->getId());

        // Persistance
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($gamePlayer);
        $entityManager->flush();

        // Routing
        return $this->redirectToRoute('lobby');
    }

    /**
     * @Route("/autoConnect", methods={"GET"}, name="auto_connect")
     */
    public function autoConnect(Request $request) 
    {
        $playerKey = $this->session->get(self::PlayerSession);
        $gameKey = $request->query->get('gameKey');

        if($playerKey != null)
        {
            return $this->redirectToRoute('already_in_game');
        }

        $game = $this->gameRepository->findByGuid($gameKey);
        if($game == null)
        {
           throw new \Exception("Game not found with key : ".$gameKey);
        }
        $playerKey = $this->getGUID();

        // Save pdo session (save in database)
        $this->session->set(self::PlayerSession, $playerKey);
        $this->session->set(self::GameSession, $gameKey);

        // Generate player randomly
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setSessionId($this->session->getId());
        $gamePlayer->setPublicKey($playerKey);

        $gamePlayers = $this->gamePlayerRepository->findBy(['game' => $game->getId()]);

        // Choose player name
        $playerNames = array_map(function($p) {
            return $p->getName();
        }, $gamePlayers);
        $gamePlayer->setName($this->random->name($playerNames));

        // Determine team and role
        $masterSpies = array_values(array_filter($gamePlayers, function($gp) {
            return $gp->getRole() === Roles::Master;
        }));
        if(\count($masterSpies) < 2)
        {
            $gamePlayer->setRole(Roles::Master);
            if(\count($masterSpies) === 0
                || $masterSpies[0]->getTeam() === Teams::Blue)
            {
                $gamePlayer->setTeam(Teams::Red);
            } 
            else 
            {
                $gamePlayer->setTeam(Teams::Blue);
            }
        }
        else 
        {
            $gamePlayer->setRole(Roles::Spy);
            $blueTeamNbr = \count($this->gamePlayerRepository->getBlueTeam($game->getId()));
            $redTeamNbr = \count($this->gamePlayerRepository->getRedTeam($game->getId()));
            if($redTeamNbr < $blueTeamNbr) 
            {
                $gamePlayer->setTeam(Teams::Red);
            } 
            else
            {
                $gamePlayer->setTeam(Teams::Blue);
            }
        }
    
        // Persistance
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($gamePlayer);
        $entityManager->flush();
        $this->session->set(self::PlayerSession, $playerKey);

        // Redirect to game
        return $this->redirectToRoute('join_game', ['gameKey' => $gameKey]);
    }

    /**
     * @Route("/start", methods={"GET"}, name="start")
     */
    public function start()
    {
        $identity = $this->session->get(self::PlayerSession);
        $currentGameKey = $this->session->get(self::GameSession);
        $playerName = 'anonyme';
        $isInGame = false;

        if(isset($identity))
        {
            $isInGame = true;
            $player = $this->gamePlayerRepository->findByGuid($identity);
            if(!isset($player))
            {
                return $this->redirectToRoute('user_disconnect');
            }
            $playerName = $player->getName();
        }

        $viewModel = [
            'playerName' => $playerName,
            'isInGame'   => $isInGame,
            'currentGameKey' => $currentGameKey,
            'errorMessage' => ''
        ];
        return $this->render('default/start.html.twig', $viewModel);
    }

    /**
     * @Route("/disconnect", methods={"GET"}, name="user_disconnect")
     */
    public function disconnect()
    {
        $this->session->remove(self::PlayerSession);
        $this->session->remove(self::GameSession);
        return $this->redirectToRoute('start');
    }

    /**
     * http://guid.us/GUID/PHP
     */
    private function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

 
}
