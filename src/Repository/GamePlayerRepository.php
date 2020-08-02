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
        $gamePlayerEntity = $this->createQueryBuilder('g')
            ->where('g.game = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getResult()
        ;
        return $gamePlayerEntity;
    }

    public function findByGuid(string $playerKey)
    {
        $playerEntity = $this->createQueryBuilder('g')
            ->andWhere('g.publicKey = :val')
            ->setParameter(':val', $playerKey)
            ->getQuery()
            ->getOneOrNullResult();
        return $playerEntity;
    }

    public function playerSessions()
    {
        $rawSql = 'SELECT * FROM sessions;';
        $stmt = $this->getEntityManager()->getConnection()->prepare($rawSql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function cleanPlayerSessions()
    {
        $rawSql = 'DELETE FROM sessions where sess_lifetime - :time < 0';
        $stmt = $this->getEntityManager()->getConnection()->prepare($rawSql);
        $params['time'] = time();
        $stmt->execute($params);
    }
   
}
