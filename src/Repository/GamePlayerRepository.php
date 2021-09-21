<?php

namespace App\Repository;

use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Entity\Teams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    public function findByGameId(int $gameId)
    {
        return $this->createQueryBuilder('g')
            ->where('g.game = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
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

    public function getMasterSpies(int $gameId)
    {
        $gps = $this->createQueryBuilder('gp')
            ->where('gp.game = :val1')
            ->andWhere('gp.role = :val2')
            ->setParameter(':val1', $gameId)
            ->setParameter(':val2', Roles::Master)
            ->getQuery()
            ->getResult()
        ;
        return $gps;
    }

    public function getBlueTeam(int $gameId)
    {
        $gps = $this->createQueryBuilder('gp')
            ->where('gp.game = :val1')
            ->andWhere('gp.team = :val2')
            ->setParameter(':val1', $gameId)
            ->setParameter(':val2', Teams::Blue)
            ->getQuery()
            ->getResult()
        ;
        return $gps;
    }

    public function getRedTeam(int $gameId)
    {
        $gps = $this->createQueryBuilder('gp')
            ->where('gp.game = :val1')
            ->andWhere('gp.team = :val2')
            ->setParameter(':val1', $gameId)
            ->setParameter(':val2', Teams::Red)
            ->getQuery()
            ->getResult()
        ;
        return $gps;
    }

    public function cleanPlayerSessions()
    {
        $rawSql = 'DELETE FROM sessions where sess_lifetime - :time < 0';
        $stmt = $this->getEntityManager()->getConnection()->prepare($rawSql);
        $params['time'] = time();
        $stmt->execute($params);
    }
   
}
