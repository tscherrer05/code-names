<?php

namespace App\Controller;

use App\CodeNames\GameInfo;
use App\Entity\Card;
use App\Entity\Game;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Ramsey\Uuid\Guid\Guid;
use App\CodeNames\GameStatus;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Entity\Teams;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Service\Random;
use Symfony\Component\Routing\Annotation\Route; // Mandatory for annotations


class DefaultController extends AbstractController
{
    private GameRepository $gameRepository;
    private SessionInterface $session;
    private GamePlayerRepository $gamePlayerRepository;
    private Random $random;

    const PlayerSession = 'playerKey';
    const GameSession = 'gameKey';
    const AnonymousName = 'anonyme';

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
    public function createGame(): RedirectResponse
    {
        $gameKey = $this->session->get(self::GameSession);
        if($gameKey !== null)
        {
            return $this->redirectToRoute('already_in_game');
        }

        $gameInfo = GameInfo::brandNew($this->random);
        $this->persist($gameInfo);
        $gameKey = $gameInfo->getGuid();

        return $this->redirectToRoute('auto_connect', [
            "gameKey" => $gameKey
        ]);
    }

    private function persist(GameInfo $gameInfo)
    {
        $entityManager = $this->getDoctrine()->getManager();

        // Mapping jeu
        $game = new Game();
        $game->setPublicKey($gameInfo->getGuid());
        $game->setStatus($gameInfo->status);
        $game->setCurrentWord($gameInfo->currentWord());
        $game->setCurrentNumber($gameInfo->currentNumber());
        $game->setCurrentTeam($gameInfo->currentTeam());

        // Mapping joueurs
        // Sauvegarder les votes de chaque joueur de la partie
        $gamePlayers = $this->gamePlayerRepository->findBy(['game' => $game->getId()]);
        foreach($gamePlayers as $gpData)
        {
            $votes = $gameInfo->getAllVotes();
            $key = $gpData->getPublicKey();
            if(array_key_exists($key, $votes))
            {
                $gpData->setX($votes[$key]->x);
                $gpData->setY($votes[$key]->y);
            }
            else
            {
                $gpData->setX(null);
                $gpData->setY(null);
            }
        }

        // Mapping cards
        $cards = $gameInfo->getAllCards();
        foreach($cards as $row)
        {
            foreach ($row as $card)
            {
                $c = new Card();
                $c->setGame($game);
                $c->setColor($card->getColor());
                $c->setReturned($card->isReturned());
                $c->setWord($card->getWord());
                $c->setX($card->getX());
                $c->setY($card->getY());
                $entityManager->persist($c);
            }
        }

        $entityManager->persist($game);
        $entityManager->flush();
    }

    /**
     * @Route("/game", methods={"GET"}, name="join_game")
     * @throws Exception
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
            throw new Exception("Player not found with guid : $playerKey");
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
        $playerName = self::AnonymousName;
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
     * @throws Exception
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
            throw new Exception("Game not found with public key : ". $gameKey);
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
            throw new Exception("Game not found with key : ".$gameKey);
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
        $gameKey = $request->query->get(self::GameSession);

        if($playerKey != null)
        {
            return $this->redirectToRoute('already_in_game');
        }

        $game = $this->gameRepository->findByGuid($gameKey);
        if($game == null)
        {
           throw new Exception("Game not found with key : ".$gameKey);
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
        $playerName = self::AnonymousName;
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
