<?php
namespace App\Controller;

use App\CodeNames\GameStatus;
use App\Entity\Colors;
use App\Entity\Roles;
use App\Repository\GamePlayerRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\GameRepository;
use App\Service\Random;
use Ratchet\ConnectionInterface;
use function count;

class RealTimeController extends AbstractController
{
    private GameRepository $gameRepository;
    private GamePlayerRepository $gamePlayerRepository;
    private Random $random;

    public function __construct(GameRepository $gameRepository, 
    GamePlayerRepository $gamePlayerRepository,
    Random $random)
    {
        $this->gameRepository       = $gameRepository;
        $this->gamePlayerRepository = $gamePlayerRepository;
        $this->random               = $random;
    }

    /**
     * 
     */
    public function startGame($params)
    {
        try {
            // TODO : sanitize input
            $gameKey = $params['gameKey'];
            $clients = $params['clients'];
            $players = $params['players'];
            $entityManager = $this->getDoctrine()->getManager();

            // Put game in "OnGoing" status
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
            $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
        } catch (Exception $exception) {
            print($exception->getMessage());
            $model = [
                'action' => 'gameStarted',
                'error' => true,
                'message' => "Erreur lors du démarrage de la partie."
            ];
            $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
        }    
    }

    /**
     * A player votes for a card.
     * [
     *  'action',
     *  'playerKey',
     *  'playerName',
     *  'x',
     *  'y',
     *  'color'
     * ]
     */
    public function vote($params)
    {
        // TODO : Sanitize input !
        $x = $params['x'];
        $y = $params['y'];
        $playerKey = $params['playerKey'];
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];

        try
        {
            // Fetch domain
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $player = $gameInfo->getPlayer($playerKey);

            // Execute rule
            $voteResult = $gameInfo->vote($player, $x, $y);

            // TODO : Propager évènements d'erreur
//            if($voteResult['ok'] !== true)
//            {
//            }

            // Map domain <-> persistence
            $this->persist($gameInfo);

            // Dispatch events
            $model = [
                'action'    => 'hasVoted',
                'playerKey' => $player->guid,
                'playerName' => $player->name,
                'x'         => $x,
                'y'         => $y,
                'color'     => $voteResult['card']->color
            ];
            $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
            
            if($voteResult['card']->returned === true)
            {
                $model = [
                    'action' => 'cardReturned',
                    'x' => $x,
                    'y' => $y,
                    'color' => $voteResult['card']->color,
                    'team' => $player->team,
                    'word' => $voteResult['card']->word
                ];
                $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
            }
        }
        catch(\InvalidArgumentException | Exception $e)
        {
            print($e->getMessage());
            print($e->getTraceAsString());
            $model = [
                'action' => 'hasVoted',
                'error' => true,
                'message' => "Erreur lors du vote du joueur $playerKey sur la carte [$x, $y]"
            ];            
            $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
        }
    }

    /**
     * Passes a team turn
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
            // Apply rule
            $gameInfo = $this->gameRepository->getByGuid($gameKey);
            $gameInfo->passTurn();

            // Persist
            $gameEntity = $this->gameRepository->findByGuid($gameKey);
            $gameEntity->setCurrentTeam($gameInfo->team);

            // Event dispatch
            // TODO : refactor into proper class
            $gamePlayers = $gameEntity->getGamePlayers()->toArray();
            $currentTeam = $gameEntity->getCurrentTeam();
            $remainingVotes = [];
            foreach($gamePlayers as $p) {
                $p->setX(null);
                $p->setY(null);
                if(Roles::Spy === $p->getRole() && $p->getTeam() === $currentTeam)
                {
                    $remainingVotes[] = $p->getPublicKey();
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $gp = $this->gamePlayerRepository->findByGuid($playerKey);
            if($gp == null) 
            {
                throw new Exception("Player not found with guid : $playerKey");
            }

            $model = [
                'action'            => 'turnPassed',
                'team'              => $gameInfo->currentTeam(),
                'remainingVotes'    => $remainingVotes
            ];
            $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
        }
        catch(Exception $e)
        {
            // TODO : système de log
            print($e->getMessage());
            print($e->getTraceAsString());
            $this->sendToOtherGamePlayers($clients, $from, $gameKey, json_encode(['error' => "Une erreur interne s'est produite."]));
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
        $from = $params['from'];

        $gp = $this->gamePlayerRepository->findByGuid($playerKey);
        if($gp == null) 
        {
            throw new Exception("Player not found with guid : $playerKey");
        }
        $gp->setConnectionId($from->resourceId);
        $this->getDoctrine()->getManager()->flush();

        $model = [
            'action' => 'playerJoined',
            'playerKey' => $playerKey,
            'playerName' => $gp->getName(),
            'playerRole' => $gp->getRole(),
            'playerTeam' => $gp->getTeam(),
            'gameKey' => $gameKey,
        ];
        $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
    }

    /**
     * Resets a game and shuffle cards' colors and words
     */
    public function resetGame($params) 
    {
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];
        
        $game = $this->gameRepository->findByGuid($gameKey);
        $cards = $game->getCards()->toArray();
        $gamePlayers = $game->getGamePlayers()->toArray();

        $numbers = [
            [Colors::Blue, 9],
            [Colors::Red, 8],
            [Colors::White, 7],
            [Colors::Black, 1],
        ];

        foreach($cards as $c) {
            $c->setReturned(false);
            $index = $this->random->rand(0, count($numbers)-1);
            $choice = $numbers[$index];
            $color = $choice[0];
            $number = $choice[1];
            $c->setColor($color);
            if($number === 1) {
                array_splice($numbers, $index, 1);
            } else {
                $numbers[$index][1]--;
            }
        }

        foreach($gamePlayers as $gp) {
            $gp->setX(null);
            $gp->setY(null);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        $model = [
            'action' => 'gameHasReset',
            'gameKey' => $gameKey
        ];
        $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
    }

    /**
     * Empties a game from all its players
     */
    public function emptyGame($params) {
        $gameKey = $params['gameKey'];
        $clients = $params['clients'];
        $em = $this->getDoctrine()->getManager();

        $model = [
            'action' => 'gameIsEmptied',
            'gameKey' => $gameKey,
            'redirectUrl' => '/disconnect'
        ];
        $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));

        foreach($this->gameRepository
                    ->findByGuid($gameKey)
                    ->getGamePlayers()
                    ->toArray() 
                as $gp) {
               $em->remove($gp); 
        }

        $em->flush();
    }

    public function leaveGame($params)
    {
        $gameKey = $params['gameKey'];
        $playerKey = $params['playerKey'];
        $clients = $params['clients'];

        $gp = $this->gamePlayerRepository->findOneBy(['publicKey' => $playerKey]);
        $em = $this->getDoctrine()->getManager();
        $em->remove($gp);
        $em->flush();

        $model = [
            'action' => 'playerLeft',
            'gameKey' => $gameKey,
            'playerKey' => $playerKey
        ];
        $this->sendToAllGamePlayers($clients, $gameKey, json_encode($model));
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

    private function sendToOtherGamePlayers(\SplObjectStorage $clients, ConnectionInterface $from, string $gameKey, string $message)
    {
        $connectionIds = array_map(
            function ($gp) {
                return $gp->getConnectionId();
            },
            $this->gameRepository->findOneBy(['publicKey' => $gameKey])
                ->getGamePlayers()
                ->toArray()
        );

        foreach ($clients as $client) 
        {
            if($client != $from && in_array($client->resourceId, $connectionIds))
            {
                $client->send($message);
            }
        }
    }

    private function sendToAllGamePlayers(\SplObjectStorage $clients, string $gameKey, string $message)
    {
        $connectionIds = array_map(
            function($gp) { return $gp->getConnectionId(); },
            $this->gameRepository->findOneBy(['publicKey' => $gameKey])
                ->getGamePlayers()
                ->toArray()
        );

        foreach($clients as $client) 
        {
            if(in_array($client->resourceId, $connectionIds))
            {
                $client->send($message);
            }
        }
    }

}