<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Ramsey\Uuid\Guid\Guid;
use App\CodeNames\GameStatus;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Entity\Teams;
use App\Repository\CardRepository;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;

class DefaultController extends AbstractController
{
    private $gameRepository;
    private $playerSession;
    private $gamePlayerRepository;
    private $cardRepository;

    const PlayerSession = 'playerKey';
    const GameSession = 'gameKey';

    public function __construct(SessionInterface $session,
        GameRepository $gameRepo,
        GamePlayerRepository $gamePlayerRepository,
        CardRepository $cardRepository)
    {
        $this->playerSession        = $session;
        $this->gameRepository       = $gameRepo;
        $this->gamePlayerRepository = $gamePlayerRepository;
        $this->cardRepository       = $cardRepository;
    }

    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/getSession", methods={"GET"}, name="get_session")
     */
    public function getSession() 
    {
        var_dump($this->playerSession->get(self::PlayerSession));
        exit;
    }

    /**
     * @Route("/setSession", methods={"GET"}, name="set_session")
     */
    public function setSession()
    {
        $this->playerSession->set(self::PlayerSession, $this->getGUID());
    }

    /**
     * @Route("/game", methods={"GET"}, name="join_game")
     */
    public function game(Request $request)
    {
        // Parsage des paramètres
        $gameKey = $request->query->get('gameKey');
        $playerKey = $this->playerSession->get(self::PlayerSession);

        // Routing
        if(!isset($gameKey))
        {
            return $this->redirectToRoute('start');
        }
        if (!isset($playerKey))
        {
            return $this->redirectToRoute(
                'get_login',
                [
                    'gameKey' => $gameKey
                ]
            );
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
        $playerKey = Guid::uuid4()->toString();

        // Save pdo session (save in database)
        $this->playerSession->set(self::PlayerSession, $playerKey);
        $this->playerSession->set(self::GameSession, $gameKey);
        
        $gamePlayer = new GamePlayer();
        $gamePlayer->setName($playerName);
        $gamePlayer->setPublicKey($playerKey);
        $gamePlayer->setGame($game);
        $gamePlayer->setSessionId($this->playerSession->getId());

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
        $playerKey = $this->playerSession->get(self::PlayerSession);
        $gameKey = $request->query->get('gameKey');

        if($playerKey != null)
        {
            return $this->redirectToRoute('join_game', ['gameKey' => $gameKey]);
        }

        $game = $this->gameRepository->findByGuid($gameKey);
        if($game == null)
        {
           throw new \Exception("Game not found with key : ".$gameKey);
        }
        $playerKey = $this->getGUID();

        // Save pdo session (save in database)
        $this->playerSession->set(self::PlayerSession, $playerKey);
        $this->playerSession->set(self::GameSession, $gameKey);

        // Generate player randomly
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        // https://stackoverflow.com/questions/4356289/php-random-string-generator
        // TODO : friendly unique name
        $gamePlayer->setName(substr(md5(rand()), 0, 7));
        $gamePlayer->setSessionId($this->playerSession->getId());
        $gamePlayer->setPublicKey($playerKey);

        $gamePlayers = $this->gamePlayerRepository
                        ->findBy(['game' => $game->getId()]);
        $masterSpies = array_values(array_filter($gamePlayers, function($gp) {
            return $gp->getRole() === Roles::Master;
        }));
        // Determine team and role
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
        $this->playerSession->set(self::PlayerSession, $playerKey);

        // Redirect to game
        return $this->redirectToRoute('join_game', ['gameKey' => $gameKey]);
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
            $player = $this->gamePlayerRepository->findByGuid($identity);
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
