<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use App\Repository\CardRepository;
use App\Repository\GamePlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
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
        try {
            // TODO : assainir input
            $gameKey = $params['gameKey'];
            $clients = $params['clients'];
            $players = $params['players'];
            $entityManager = $this->getDoctrine()->getManager();

            // Mettre le jeu au statut "OnGoing"
            $gameEntity = $this->gameRepository->findByGuid($gameKey);
            $gameEntity->setStatus(GameStatus::OnGoing);

            // Ajouter les joueurs aux jeu
            // TODO : extraire cela dans un objet métier
            foreach($players as $p) {
                $gpEntity = $this->gamePlayerRepository->findByGuid($p['playerKey']);
                $gpEntity->setRole($p['role']);
                $gpEntity->setTeam($p['team']);
            }

            $entityManager->flush();

            // Envoyer un message pour dire à tous les clients que le jeu a démarré.
            $model = [
                'action' => 'gameStarted',
                'gameKey' => $gameKey
            ];
            $this->sendToAllClients($clients, json_encode($model));
        } catch (\Exception $exception) {
            print($exception->getMessage());
            $model = [
                'action' => 'gameStarted',
                'error' => true,
                'message' => "Erreur lors du démarrage de la partie."
            ];
            $this->sendToAllClients($clients, json_encode($model));
        }    
    }


    public function vote($params)
    {
        // TODO : Sanitize input !
        $x = $params['x'];
        $y = $params['y'];
        $playerKey = $params['playerKey'];
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];

        // Effectuer la commande demandée par l'utilisateur en passant les paramètres à la racine du graphe.
        try
        {
            // Récupérer la racine du graphe
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $player = $gameInfo->getPlayer($playerKey);

            // Exécuter les règles du jeu (change l'état du jeu)
            $voteResult = $gameInfo->vote($player, $x, $y);

            if($voteResult['ok'] !== true)
            {
                // TODO : Propage évènements d'erreur
            }

            // Mapping de la racine du graphe avec la persistance

            // TODO : $this->gameRepository->save($gameInfo);
            
            // Mapping jeu
            $game = $this->gameRepository->findOneBy(['publicKey' => $gameKey]);
            $game->setStatus($gameInfo->status);
            $game->setCurrentWord($gameInfo->currentWord());
            $game->setCurrentNumber($gameInfo->currentNumber());
            $game->setCurrentTeam($gameInfo->currentTeam());

            // Mapping joueurs
            // Sauvegarder les votes de chaque joueur de la partie
            foreach($game->getGamePlayers() as $gpData)
            {
                $arr = $gameInfo->getAllVotes();
                $key = $gpData->getPublicKey();
                if(array_key_exists($key, $arr))
                {
                    $gpData->setX($arr[$key]->x);
                    $gpData->setY($arr[$key]->y);
                }
                else 
                {
                    $gpData->setX(null);
                    $gpData->setY(null);
                }
            }
            $cards = $gameInfo->getAllCards();
            $cardEntities = $game->getCards();
            foreach($cardEntities as $c)
            {
                $c->setReturned($cards[$c->getX()][$c->getY()]->returned);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();     
                
            // Propage des évènements aux clients connectés
            $model = [
                'action'    => 'hasVoted',
                'playerKey' => $player->guid,
                'playerName' => $player->name,
                'x'         => $x,
                'y'         => $y,
                'color'     => $voteResult['card']->color
            ];
            $this->sendToAllClients($clients, json_encode($model));
            
            if($voteResult['card']->returned === true)
            {
                $model = [
                    'action' => 'cardReturned',
                    'x' => $x,
                    'y' => $y,
                    'color' => $voteResult['card']->color
                ];
                $this->sendToAllClients($clients, json_encode($model));
            }
        }
        catch(\InvalidArgumentException $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            $model = [
                'action' => 'hasVoted',
                'error' => true,
                'message' => "Erreur lors du vote du joueur $playerKey sur la carte [$x, $y]"
            ];            
            $this->sendToAllClients($clients, json_encode($model));
        }
        catch(\Exception $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            $model = [
                'action' => 'hasVoted',
                'error' => true,
                'message' => "Erreur lors du vote du joueur $playerKey sur la carte [$x, $y]"
            ];
            $this->sendToAllClients($clients, json_encode($model));
        }
    }

    private function mapGame($gameKey, $playerKey, $gameInfo, $clients, $x, $y) 
    {
        // Jeu
        $game = $this->gameRepository->findOneBy(['publicKey' => $gameKey]);
        $game->setStatus($gameInfo->status);
        $game->setCurrentWord($gameInfo->currentWord());
        $game->setCurrentNumber($gameInfo->currentNumber());
        $game->setCurrentTeam($gameInfo->currentTeam());
        
        // Joueur
        $gps = $this->gamePlayerRepository->findByGame($game->getId());
        foreach($gps as $gpEntity) {
            $gpDomain = $gameInfo->getPlayer($gpEntity->getPublicKey());
            if($gpEntity == null)
            {
                $model = [
                    'action' => 'error',
                    'message' => "Erreur lors du vote du joueur $playerKey sur la carte [$x, $y] : joueur non trouvé."
                ];
                $this->sendToAllClients($clients, json_encode($model));
            }
            $gpEntity->setX($gpDomain->x);
            $gpEntity->setY($gpDomain->y);
        }

        // Carte
        $card = $this->cardRepository->findOneBy(['x' => $x, 'y' => $y]);
        if($card == null)
        {
            $model = [
                'action' => 'hasVoted',
                'error' => true,
                'message' => "Erreur lors du vote du joueur $playerKey sur la carte [$x, $y] : carte non trouvée."
            ];
            $this->sendToAllClients($clients, json_encode($model));
        }
        $returned = $gameInfo->board()->isCardReturned($x, $y);
        $card->setReturned($returned);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
    }

    public function passTurn($params)
    {
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];
        $from = $params['from'];

        try
        {
            // Règle du jeu
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $gameInfo->passTurn();

            // Persistance
            $gameEntity = $this->gameRepository->findByGuid($gameKey);
            $gameEntity->setCurrentTeam($gameInfo->team);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

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