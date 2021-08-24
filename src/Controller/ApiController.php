<?php
namespace App\Controller;

use App\Entity\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\CardRepository;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use Exception;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    private GameRepository $gameRepository;
    private SessionInterface $playerSession;
    private GamePlayerRepository $gamePlayerRepository;
    private CardRepository $cardRepository;

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

    /**
     * @Route("/cards", methods={"GET"}, name="get_cards")
     */
    public function cards(Request $request): JsonResponse
    {
        // Parsing
        $gameKey = $request->query->get('gameKey');

        // Queries
        $gameEntity  = $this->gameRepository->findByGuid($gameKey);
        $cards       = $this->cardRepository->findBy(['game' => $gameEntity->getId()]);

        // Building model
        $models = [];
        foreach ($cards as $c) {
            $models[] = [
                'color'     => $c->getColor(),
                'returned'  => $c->getReturned(),
                'word'      => $c->getWord(),
                'x'         => $c->getX(),
                'y'         => $c->getY()
            ];
        }

        return new JsonResponse($models);
    }

    /**
     * @Route("/gameInfos", methods={"GET"}, name="get_game_infos")
     */
    public function gameInfos(Request $request): JsonResponse
    {
        try {
            // Parsing
            $gameKey = $request->query->get('gameKey');
            $playerKey = $this->playerSession->get(DefaultController::PlayerSession);

            // Queries
            $gameEntity  = $this->gameRepository->findByGuid($gameKey);
            if($gameEntity == null)
            {
                throw new Exception("Game not found with guid : $gameKey");
            }
            $gp = $this->gamePlayerRepository->findByGuid($playerKey);
            if($gp == null) 
            {
                throw new Exception("Player not found with guid : $playerKey");
            }
            $cards = $this->cardRepository->findBy(['game' => $gameEntity->getId()]);

            $currentTeam = $gameEntity->getCurrentTeam();
            $gamePlayers = $gameEntity->getGamePlayers()->toArray();
            $allPlayers = [];
            $currentTeamSpies = [];
            $currentTeamPlayers = [];
            $remainingVotes = [];
            $currentVotes = [];

            $models = [];
            foreach ($cards as $c) {
                $models[] = [
                    'color'     => $c->getColor(),
                    'returned'  => $c->getReturned(),
                    'word'      => $c->getWord(),
                    'x'         => $c->getX(),
                    'y'         => $c->getY()
                ];
            }

            foreach($gamePlayers as $p) 
            {
                $playerModel = ['name' => $p->getName(), 'team' => $p->getTeam(), 'role' => $p->getRole()];
                $allPlayers[$p->getPublicKey()] = $playerModel;
                if($currentTeam === $p->getTeam())
                {
                    $currentTeamPlayers[$p->getPublicKey()] = $playerModel;
                }
                if(Roles::Spy === $p->getRole())
                {
                    $currentTeamSpies[$p->getPublicKey()] = $playerModel;
                    if($p->getTeam() === $currentTeam) 
                    {
                        if($p->getX() == null && $p->getY() == null) 
                        {
                            $remainingVotes[] = $p->getPublicKey();
                        }
                        else 
                        {
                            $cardKey = $p->getX().$p->getY();
                            $currentVotes[$p->getPublicKey()] = $cardKey;
                        }
                    }

                }
            }

            $model = [
                'gameKey'               => $gameEntity->getPublicKey(),
                'currentNumber'         => $gameEntity->getCurrentNumber(),
                'currentWord'           => $gameEntity->getCurrentWord(),
                'currentTeam'           => $gameEntity->getCurrentTeam(),
                'playerName'            => $gp->getName(),
                'playerKey'             => $gp->getPublicKey(),
                'playerTeam'            => $gp->getTeam(),
                'playerRole'            => $gp->getRole(),
                'canPassTurn'           => $gp->getRole() === Roles::Master
                                            && $gp->getTeam() === $gameEntity->getCurrentTeam(),
                'allPlayers'            => $allPlayers,
                'currentTeamPlayers'    => $currentTeamPlayers,
                'currentTeamSpies'      => $currentTeamSpies,    
                'currentVotes'          => $currentVotes,
                'remainingVotes'        => $remainingVotes,
                'cards'                 => $models
            ];

            return new JsonResponse($model);
        } catch(Exception $e) {
            $model = [
                'error' => true,
                'message' => $e->getMessage(),
                'stack' => $e->getTrace()
            ];
            return new JsonResponse($model);
        }
        
    }
}