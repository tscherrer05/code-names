<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * Mappe les entités sur les objets logiques
     * @throws Exception
     */
    private function createGame(Game $gameEntity)
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

    public function commit()
    {
        $this->entityManager->flush();
    }
}
