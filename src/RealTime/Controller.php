<?php
namespace App\RealTime;
use App\Repository\GameRepository;


class Controller
{
    private $gameRepository;

    public function __construct()
    {
        $this->gameRepository = new GameRepository();
    }

    public function vote($args)
    {
        // TODO : Sanitize !
        $gameId = $args['gameId'];
        $playerId = $args['playerId'];
        $x = $args['x'];
        $y = $args['y'];

        // Récupérer l'état du jeu en base de données
        $gameInfo = $this->gameRepository->get($gameId);
        $player = $gameInfo->getPlayer($playerId);

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            $gameInfo->vote($playerId, $x, $y);

            // Sauvegarder le nouvel état en base
            // $this->gameRepository->saveGameInfo($gameInfo);

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
            var_dump($e);
        }
        catch(\Exception $e)
        {
            var_dump($e);
        }
    }

    public function returnCard($args)
    {
        // TODO : Sanitize !
        $x = $args["x"];
        $y = $args["y"];

        $result = array(
            "action" => "returnCard",
            "x" => $x,
            "y" => $y
        );

        return \json_encode($result);
    }
}

?>