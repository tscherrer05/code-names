<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CardRepository;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use Exception;

class ApiController extends AbstractController
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

    /**
     * @Route("/cards", methods={"GET"}, name="get_cards")
     */
    public function cards(Request $request)
    {
        // Parsing
        $gameKey = $request->query->get('gameKey');

        // Queries
        $gameEntity  = $this->gameRepository->findByGuid($gameKey);
        $cards       = $this->cardRepository->findBy(['game' => $gameEntity->getId()]);
        $gamePlayers = $gameEntity->getGamePlayers()->toArray();

        // Building model
        $models = [];
        foreach ($cards as $c) {
            $models[] = [
                'color'     => $c->getColor(),
                'returned'  => $c->getReturned(),
                'word'      => $c->getWord(),
                'x'         => $c->getX(),
                'y'         => $c->getY(),
                'voters'    => array_map(function($gp) {
                                    return [
                                        'playerKey' => $gp->getPublicKey(),
                                        'name' => $gp->getName()
                                    ];
                                },
                                array_filter($gamePlayers, function($gp) use($c)
                                {
                                    return $gp->getX() === $c->getX() && $gp->getY() === $c->getY();
                                }))
            ];
        }

        return new JsonResponse($models);
    }

    /**
     * @Route("/gameInfos", methods={"GET"}, name="get_game_infos")
     */
    public function gameInfos(Request $request)
    {
        try {
            // Parsing
            $gameKey = $request->query->get('gameKey');
            $playerKey = $this->playerSession->get(DefaultController::PlayerSession);

            // Queries
            $gameEntity  = $this->gameRepository->findByGuid($gameKey);
            $gp = $this->gamePlayerRepository->findByGuid($playerKey);

            $model = [
                'gameKey'               => $gameEntity->getPublicKey(),
                'currentNumber'         => $gameEntity->getCurrentNumber(),
                'currentWord'           => $gameEntity->getCurrentWord(),
                'currentTeam'           => $gameEntity->getCurrentTeam(),
                'playerName'            => $gp->getName(),
                'playerKey'             => $gp->getPublicKey(),
                'playerTeam'            => $gp->getTeam() ? 1 : 2,
                'remainingVotes'        => []
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