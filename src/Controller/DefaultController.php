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
use Ramsey\Uuid\Nonstandard\Uuid;

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
        $gameEntity  = $this->gameRepository->findByGuid($gameKey);
        $gamePlayers = $this->gamePlayerRepository->findBy(['game' => $gameEntity->getId()]);
        $cards       = $this->cardRepository->findBy(['game' => $gameEntity->getId()]);

        // Construction de la réponse

        // Joueurs qui ont pas voté
        $notVoted = array_filter($gamePlayers, function($gp) 
        {
            return $gp->getX() == null && $gp->getY() == null;
        });

        $twoDimCards = [];
        foreach ($cards as $c) {
            $twoDimCards[$c->getX()][$c->getY()] = [
                'color'     => $c->getColor(),
                'returned'  => $c->getReturned(),
                'word'      => $c->getWord(),
                'x'         => $c->getX(),
                'y'         => $c->getY(),
                'voters'   => array_map(function($gp) {
                    return [
                        'playerKey' => $gp->getPlayer()->getPlayerKey(),
                        'name' => $gp->getPlayer()->getName()
                    ];
                },
                array_filter($gamePlayers, function($gp) use($c)
                {
                    return $gp->getX() === $c->getX() && $gp->getY() === $c->getY();
                }))
            ];
        }

        $viewModel = [
            "gameKey"           => $gameKey,
            "announcedNumber"   => $gameEntity->getCurrentNumber(),
            "announcedWord"     => $gameEntity->getCurrentWord(),
            "currentTeam"       => $gameEntity->getCurrentTeam(),
            "currentPlayerKey"  => $playerKey,
            "currentPlayerName" => $currPlayer->getName(),
            "currentPlayerRole" => $currPlayer->getRole(),
            "currentPlayerTeam" => $currPlayer->getTeam(),
            "isGuesser"         => true,
            "cards"             => $twoDimCards,
            "notVoted"          => array_map(function($gp) 
                                    { 
                                        return [
                                            'playerKey' => $gp->getPublicKey(),
                                            'name'      => $gp->getName()
                                        ];
                                    }, $notVoted)
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
        $playSession = $this->playerSession->get(self::PlayerSession);
        $gameKey = $request->query->get('gameKey');
        if($playSession != null)
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
        // TODO : friendly name
        $gamePlayer->setName(substr(md5(rand()), 0, 7));
        $gamePlayer->setSessionId($this->playerSession->getId());
        $gamePlayer->setPublicKey($playerKey);

        // Determine team and role
        // TODO : random role and team
        $gamePlayer->setRole(Roles::Master);
        $gamePlayer->setTeam(Teams::Blue);

        // Persistance
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($gamePlayer);
        $entityManager->flush();

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
