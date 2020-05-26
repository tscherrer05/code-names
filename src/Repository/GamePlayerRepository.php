<?php

namespace App\Repository;

use App\Entity\GamePlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GamePlayer|null find($id, $lockMode = null, $lockVersion = null)
 * @method GamePlayer|null findOneBy(array $criteria, array $orderBy = null)
 * @method GamePlayer[]    findAll()
 * @method GamePlayer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GamePlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamePlayer::class);
    }

    public function findByGame($gameId)
    {
        $playerEntity = $this->createQueryBuilder('g')
            ->andWhere('g.game = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getResult()
        ;
        return $playerEntity;
    }
    
}
