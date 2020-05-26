<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[]    findAll()
 * @method Player[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function findByGuid(string $playerKey)
    {
        $playerEntity = $this->createQueryBuilder('g')
            ->andWhere('g.playerKey = :val')
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
