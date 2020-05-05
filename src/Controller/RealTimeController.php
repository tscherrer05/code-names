<?php
namespace App\Controller;

use App\Repository\GameInfoRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class RealTimeController extends AbstractController
{
    const PLAYER_SESSION_NAME = 'playerId';
    const GAME_SESSION_NAME = 'gameId';

    public function __construct()
    {
        // TODO : Inject dependencies
        $this->gameRepository = new GameInfoRepository();
    }

    /**
     * @Route("/vote", methods={"GET"})
     */
    public function vote($request)
    {
        echo "Vote !\n";
        // TODO : Sanitize !
        $x = $request['x'];
        $y = $request['y'];
        $playerId = $request['playerId'];
        $gameId = $request['gameId'];

        echo 'PlayerId : ' . $playerId . "\n";
        echo 'GameId : ' . $gameId . "\n";
        echo 'x : ' . $x . "\n";
        echo 'y : ' . $y . "\n";

        // Récupérer l'état du jeu en base de données
        $gameInfo = $this->gameRepository->get($gameId);
        $player = $gameInfo->getPlayer($playerId);

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            $gameInfo->vote($player, $x, $y);

            // Sauvegarder le nouvel état en base
            // $this->gameRepository->addVote($gameId, $playerId, $x, $y);

            // $this->gameRepository->commit();

            // Retourner le résultat à l'utilisateur
            $model = [
                'action' => 'vote',
                'playerName' => $player->name,
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