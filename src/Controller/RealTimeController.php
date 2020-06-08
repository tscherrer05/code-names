<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use App\Repository\GamePlayerRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RealTimeController extends AbstractController
{
    private $gameRepository;
    private $gamePlayerRepository;

    public function __construct(GameRepository $gameRepository, 
    GamePlayerRepository $gamePlayerRepository, SessionInterface $session)
    {
        $this->gameRepository       = $gameRepository;
        $this->gamePlayerRepository = $gamePlayerRepository;
    }

    /**
     * @Route("/game", methods={"POST"}, name="start_game")
     */
    public function startGame($params)
    {
        $gameKey = $params['gameKey'];

        // TODO : S'il n'y a aucune erreur

        // Mettre le jeu au statut "OnGoing"
        $gameEntity = $this->gameRepository->findByGuid($gameKey);
        $gameEntity->setStatus(GameStatus::OnGoing);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        // Envoyer un message pour dire à tous les clients que le jeu a démarré.
        $model = [
            'action' => 'startGame',
            'gameKey' => $gameKey
        ];
        return json_encode($model);
        // TODO : Else return errors

    }

    /**
     * @Route("/vote", methods={"GET"})
     */
    public function vote($params)
    {
        // TODO : Sanitize input !
        $x = $params['x'];
        $y = $params['y'];
        $playerKey = $params['playerKey'];
        $gameKey = $params['gameKey'];

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            // Récupérer la racine du graphe
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $player = $gameInfo->getPlayer($playerKey);

            // Exécuter les règles du jeu (change l'état du jeu)
            $gameInfo->vote($player, $x, $y);

            // Persister le nouvel état du jeu en base 
            // (aucune logique métier ici. Les règles du jeu n'influent pas ce type de code)
            // OP OP OP OPA MAPPING TIME
            $game = $this->gameRepository->findOneBy(['publicKey' => $gameKey]);
            $game->setStatus($gameInfo->status);
            $game->setCurrentWord($gameInfo->currentWord());
            $game->setCurrentNumber($gameInfo->currentNumber());
            $game->setCurrentTeam($gameInfo->currentTeam());
            $gp = $this->gamePlayerRepository->findOneBy(['id' => $player->id]);
            if($gp == null)
                throw new Exception("Game player not found with id : " . $player->id);
            $gp->setX($x);
            $gp->setY($y);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            
            // Retourner le résultat à l'utilisateur
            // action à effectuer côté client
            // clé du jeu
            // clé du joueur qui a voté
            // coordonnées
            $model = [
                'action' => 'vote',
                'gameKey' => $gameKey,
                'playerKey' => $player->guid,
                'x' => $x,
                'y' => $y
            ];
            return json_encode($model);
        }
        catch(\InvalidArgumentException $e)
        {
            // Gérer les éventuelles erreurs retournées par la racine du graphe.
            return json_encode(['error' => $e->getMessage()]);
        }
        catch(\Exception $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            return json_encode(['error' => "Une erreur interne s'est produite."]);
        }
    }

}