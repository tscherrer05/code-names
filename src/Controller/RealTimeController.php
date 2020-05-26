<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RealTimeController extends AbstractController
{
    private $gameRepository;
    private $playerSession;

    public function __construct(GameRepository $gameRepository, SessionInterface $session)
    {
        $this->gameRepository  = $gameRepository;
        $this->playerSession = $session;
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
    public function vote($request)
    {
        // TODO : Sanitize !
        $x = $request['x'];
        $y = $request['y'];
        $playerKey = $request['playerKey'];
        $gameKey = $request['gameKey'];

        // Récupérer l'état du jeu en base de données
        $gameInfo = $this->gameRepository->getByGuid($gameKey);
        $player = $gameInfo->getPlayer($playerKey);

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            // $gameInfo->vote($player, $x, $y);

            // TODO : Sauvegarder le nouvel état en base
            // $this->gameRepository->addVote($gameId, $playerId, $x, $y);

            // $this->gameRepository->commit();

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
            // TODO
            var_dump($e->getMessage());
            return json_encode($e->getMessage());
        }
        catch(\Exception $e)
        {
            var_dump($e->getMessage());
            return json_encode("Une erreur s'est produite. Essayez de redémarrer votre ordi pour voir ?");
        }
    }

}