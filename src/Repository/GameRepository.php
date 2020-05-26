<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\Game;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Player;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findByGuid($gameKey)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    // Model object
    public function getById(int $gameId)
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.id = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
        return $this->createGame($gameEntity);
    }

    // Model object
    public function getByGuid(string $gameKey)
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.publicKey = :val')
            ->setParameter(':val', $gameKey)
            ->getQuery()
            ->getOneOrNullResult();
        return $this->createGame($gameEntity);
    }

    // Entity
    public function findByGuid(string $gameKey)
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.publicKey = :val')
            ->setParameter(':val', $gameKey)
            ->getQuery()
            ->getOneOrNullResult();
        return $gameEntity;
    }

    private function createGame(Game $gameEntity)
    {
        if($gameEntity == null)
        {
            throw new \Exception('Game does not exist in db.');
        }

        $cards = $gameEntity->getCards();
        $gamePlayers = $gameEntity->getGamePlayers();

        // Build votes
        // TODO : change db model so it is not so awkward.
        $votes = array();
        foreach($gamePlayers as $gp)
        {
            foreach ($cards as $c) 
            {
                if($c->x == $gp->x && $c->y == $gp->y)
                {
                    $votes[$gp->playerId] = $c;
                }
            }
        }

        $players = $gamePlayers->map(function($gp) {
            $playerEntity = $gp->getPlayer();
            $play = new Player($gp->getId(), $playerEntity->getName(), $gp->getTeam(), $gp->getRole());
            $play->guid = $playerEntity->getPlayerKey();
            return $play;
        });

        $board = new Board($cards->toArray(), $votes);
        $gameInfo = new GameInfo(
            $board, 
            $gameEntity->getCurrentTeam(), 
            $gameEntity->getCurrentWord(), 
            $gameEntity->getCurrentNumber(), 
            $players->toArray());
        $gameInfo->id = $gameEntity->getId();
        $gameInfo->guid = $gameEntity->getPublicKey();
        $gameInfo->status = $gameEntity->getStatus();
    
        return $gameInfo;
    }

    public function commit()
    {
        $this->entityManager->flush();
    }
}
