<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\Game;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Card;
use App\CodeNames\Player;
use App\Entity\GamePlayer;
use Exception;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    // Model object

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function getById(int $gameId): GameInfo
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.id = :val')
            ->setParameter(':val', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
        return $this->createGame($gameEntity);
    }

    // Model object

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function getByGuid(string $gameKey): GameInfo
    {
        $gameEntity = $this->createQueryBuilder('g')
            ->andWhere('g.publicKey = :val')
            ->setParameter(':val', $gameKey)
            ->getQuery()
            ->getOneOrNullResult();
        return $this->createGame($gameEntity);
    }

    // Entity

    /**
     * Return a game entity with a given game key. Null if not found.
     * @throws NonUniqueResultException
     */
    public function findByGuid(string $gameKey) : ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.publicKey = :val')
            ->setParameter(':val', $gameKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Map data from DB on logic objects
     * @throws Exception
     */
    private function createGame(Game $gameEntity): GameInfo
    {
        if($gameEntity == null)
        {
            throw new Exception('Game does not exist in db.');
        }

        $cardEntities = $gameEntity->getCards()->toArray();
        $cards = [];
        $twoDimCards = [];
        foreach ($cardEntities as $c) 
        {
            $card = new Card(
                $c->getWord(), 
                $c->getColor(), 
                $c->getX(),
                $c->getY(),
                $c->getReturned()
            );
            $cards[] = $card;
            $twoDimCards[$card->x][$card->y] = $card;
        }

        // Build votes
        // TODO : change db model so it is not so awkward.
        $gamePlayers = $this->getEntityManager()
                        ->getRepository(GamePlayer::class)
                        ->findBy(['game' => $gameEntity->getId()]);
        $votes = [];
        $players = [];
        foreach($gamePlayers as $gp)
        {
            $players[] = new Player($gp->getPublicKey(), $gp->getName(), $gp->getTeam(), $gp->getRole());
            if($gp->getX() !== null 
                && $gp->getY() !== null) 
            {
                foreach ($cardEntities as $c)
                {
                        if($c->getX() == $gp->getX() 
                            && $c->getY() == $gp->getY())
                        {
                            $votes[$gp->getPublicKey()] = new Card(
                                $c->getWord(), $c->getColor(),
                                $c->getX(), $c->getY(), $c->getReturned());
                        }
                }
            }
        }

        $board = new Board($twoDimCards, $votes);
        $gameInfo = new GameInfo(
            $board,
            $players,
            $gameEntity->getCurrentTeam(),
            $gameEntity->getCurrentWord(),
            $gameEntity->getCurrentNumber());
        $gameInfo->id = $gameEntity->getId();
        $gameInfo->guid = $gameEntity->getPublicKey();
        $gameInfo->status = $gameEntity->getStatus();

        return $gameInfo;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function commit()
    {
        $this->getEntityManager()->flush();
    }
}
