<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Game;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Player;
use App\CodeNames\Card;
use Ramsey\Uuid\Uuid;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
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

    /**
     * @return Game[] Returns an array of Game objects
     */
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;

    }

    /*
    public function findOneBySomeField($value): ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function get(int $gameId)
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.id = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
        if($gameEntity == null)
        {
            throw new \Exception('Game not found with id : ' . $gameId);
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
            return new Player($gp->id, $gp->getPlayer()->name, $gp->getTeam(), $gp->getRole());
        });

        $board = new Board($cards, $votes);
        $gameInfo = new GameInfo(
            $board, 
            $gameEntity->getCurrentTeam(), 
            $gameEntity->getCurrentWord(), 
            $gameEntity->getCurrentNumber(), 
            $players);
    
        return $gameInfo;
    }

    public function commit()
    {
        $this->entityManager->flush();
    }
}
