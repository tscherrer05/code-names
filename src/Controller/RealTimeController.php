<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use App\Entity\Roles;
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

            // Mapping domaine <-> persistance
            $this->persist($gameInfo);

            // TODO : $this->gameRepository->save($gameInfo);

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
                    'color' => $voteResult['card']->color,
                    // Pass remaining votes ?
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

    /**
     * Passes a team turn
     * @return TurnPassedEvent
     * [
     *  'action',
     *  'team',
     *  'remainingVotes'
     * ]
     */
    public function passTurn($params)
    {
        $gameKey = $params['gameKey'];
        $playerKey = $params['playerKey'];
        $clients = $params['clients'];
        $from = $params['from'];

        try
        {
            // Game rules
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $gameInfo->passTurn();

            // Persistance
            $gameEntity = $this->gameRepository->findByGuid($gameKey);
            $gameEntity->setCurrentTeam($gameInfo->team);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            // Event dispatch
            // TODO : refactor into proper class
            $spies = array_filter(
                array_map(
                    function($p) use($gameEntity) { 
                        if($gameEntity->getCurrentTeam() == $p->getTeam()
                            && $p->getRole() == Roles::Spy) {
                            return $p;
                        }
                    },
                    $gameEntity->getGamePlayers()->toArray()
                )
            );
            $remainingVotes = [];
            foreach($spies as $p) {
                if($p->getX() == null && $p->getY() == null) {
                    $remainingVotes[] = $p->getPublicKey();
                }
            }

            $gp = $this->gamePlayerRepository->findByGuid($playerKey);
            if($gp == null) 
            {
                throw new \Exception("Player not found with guid : $playerKey");
            }

            // TODO : how to test event dispatch ?
            $model = [
                'action'            => 'turnPassed',
                'team'              => $gameInfo->currentTeam(),
                'canPassTurn'       => $gp->getRole() === Roles::Master
                                         && $gp->getTeam() === $gameEntity->getCurrentTeam(),
                'remainingVotes'    => $remainingVotes
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

    /**
     * Notifies clients about a connected player
     */
    public function connectPlayer($params)
    {
        $gameKey = $params['gameKey'];
        $playerKey = $params['playerKey'];
        $clients = $params['clients'];

        $gp = $this->gamePlayerRepository->findByGuid($playerKey);
        if($gp == null) 
        {
            throw new \Exception("Player not found with guid : $playerKey");
        }

        $model = [
            'action' => 'playerJoined',
            'playerKey' => $playerKey,
            'playerName' => $gp->getName(),
            'playerRole' => $gp->getRole(),
            'playerTeam' => $gp->getTeam(),
            'gameKey' => $gameKey,
        ];
        $this->sendToAllClients($clients, json_encode($model));
    }

    // TODO : move into repo ?
    private function persist($gameInfo) 
    {
        $gameKey = $gameInfo->getGuid();

        // Mapping jeu
        $game = $this->gameRepository->findOneBy(['publicKey' => $gameKey]);
        $game->setStatus($gameInfo->status);
        $game->setCurrentWord($gameInfo->currentWord());
        $game->setCurrentNumber($gameInfo->currentNumber());
        $game->setCurrentTeam($gameInfo->currentTeam());

        // Mapping joueurs
        // Sauvegarder les votes de chaque joueur de la partie
        $gamePlayers = $this->gamePlayerRepository->findBy(['game' => $game->getId()]);
        foreach($gamePlayers as $gpData)
        {
            $votes = $gameInfo->getAllVotes();
            $key = $gpData->getPublicKey();
            if(array_key_exists($key, $votes))
            {
                $gpData->setX($votes[$key]->x);
                $gpData->setY($votes[$key]->y);
            }
            else 
            {
                $gpData->setX(null);
                $gpData->setY(null);
            }
        }

        // Mapping cards
        $cards = $gameInfo->getAllCards();
        $cardEntities = $game->getCards();
        foreach($cardEntities as $c)
        {
            $c->setReturned($cards[$c->getX()][$c->getY()]->returned);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
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