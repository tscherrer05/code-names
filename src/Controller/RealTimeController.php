<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use App\Repository\CardRepository;
use App\Repository\GamePlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use Exception;
use Ratchet\ConnectionInterface;

class RealTimeController extends AbstractController
{
    private $gameRepository;
    private $gamePlayerRepository;

    public function __construct(GameRepository $gameRepository, 
    GamePlayerRepository $gamePlayerRepository, CardRepository $cardRepository)
    {
        $this->gameRepository       = $gameRepository;
        $this->gamePlayerRepository = $gamePlayerRepository;
        $this->cardRepository       = $cardRepository;
    }

    public function startGame($params)
    {
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];

        // Mettre le jeu au statut "OnGoing"
        $gameEntity = $this->gameRepository->findByGuid($gameKey);
        $gameEntity->setStatus(GameStatus::OnGoing);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        // Envoyer un message pour dire à tous les clients que le jeu a démarré.
        $model = [
            'action' => 'gameStarted',
            'gameKey' => $gameKey
        ];
        $this->sendToAllClients($clients, json_encode($model));
    }

    public function vote($params)
    {
        // TODO : Sanitize input !
        $x = $params['x'];
        $y = $params['y'];
        $playerKey = $params['playerKey'];
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];
        $from = $params['from'];

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            // Récupérer la racine du graphe
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $player = $gameInfo->getPlayer($playerKey);

            // Exécuter les règles du jeu (change l'état du jeu)
            $gameInfo->vote($player, $x, $y);

            // Persister le nouvel état du jeu en base
            // (aucune logique métier à partir d'ici)
            // Mapper les objets modèle et objets métier
            
            // Jeu
            $game = $this->gameRepository->findOneBy(['publicKey' => $gameKey]);
            $game->setStatus($gameInfo->status);
            $game->setCurrentWord($gameInfo->currentWord());
            $game->setCurrentNumber($gameInfo->currentNumber());
            $game->setCurrentTeam($gameInfo->currentTeam());
            
            // Joueur
            $gp = $this->gamePlayerRepository->findOneBy(['id' => $player->id]);
            if($gp == null)
            {
                throw new Exception("Game player not found with id : " . $player->id);
            }
            $gp->setX($x);
            $gp->setY($y);

            // Carte
            $card = $this->cardRepository->findOneBy(['x' => $x, 'y' => $y]);
            if($card == null)
            {
                throw new Exception("Card not found with coord : x {$x} y {$y}");
            }
            $returned = $gameInfo->board()->isCardReturned($x, $y);
            $card->setReturned($returned);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            
            // Propage des évènements aux clients connectés
            $model = [
                'action'    => 'hasVoted',
                'playerKey' => $player->guid,
                'playerName' => $player->name,
                'x'         => $x,
                'y'         => $y,
                'color'     => $card->getColor()
            ];
            $this->sendToAllClients($clients, json_encode($model));
            
            if($returned)
            {
                $model = [
                    'action' => 'cardReturned',
                    'x' => $x,
                    'y' => $y,
                    'color' => $card->getColor()
                ];
                $this->sendToAllClients($clients, json_encode($model));
            }
        }
        catch(\InvalidArgumentException $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            // Gérer les éventuelles erreurs retournées par la racine du graphe.
            $this->sendToAllClients($clients, json_encode(['error' => $e->getMessage()]));
        }
        catch(\Exception $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            $this->sendToAllClients($clients, $from, json_encode(['error' => "Une erreur interne s'est produite."]));
        }
    }

    public function passTurn($params)
    {
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];
        $from = $params['from'];

        try
        {
            // Récupérer la racine du graphe
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $gameInfo->passTurn();

            $model = [
                'team' => $gameInfo->currentTeam()
            ];
            $this->sendToAllClients($clients, json_encode($model));
        }
        catch(\Exception $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            $this->sendToOtherClients($clients, $from, json_encode(['error' => "Une erreur interne s'est produite."]));
        }
    }

    private function sendToOtherClients(\SplObjectStorage $clients, ConnectionInterface $from, string $message)
    {
        foreach ($clients as $client) 
        {
            if($client != $from)
            {
                $client->send($message);
            }
        }
    }

    private function sendToAllClients(\SplObjectStorage $clients, string $message)
    {
        foreach ($clients as $client) 
        {
            $client->send($message);
        }
    }

}