<?php
namespace App\Tests\Controller;

use App\CodeNames\GameStatus;
use App\Controller\RealTimeController;
use App\DataFixtures\TestFixtures;
use App\Entity\Card;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Teams;
use SplObjectStorage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RealTimeControllerTest extends WebTestCase
{
    private RealTimeController $service;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function setUp() :void
    {
        // start the symfony kernel
        self::bootKernel();

        // gets the special container that allows fetching private services
        $container = self::$container;

        // now we can instantiate our service
        $this->service = $container->get('realtime');

        $this->entityManager = $container->get('doctrine')->getManager();
    }

    public function testVoteNominal()
    {
        $playerKey = TestFixtures::PlayerKey1;
        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => $playerKey,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $parsed = json_decode($result, true);

        $gp = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findOneBy(['publicKey' => $playerKey])
        ;

        // Assert retour
        // TODO : Trouver comment intercepter ces messages envoyés aux clients
        // Le mock n'est pas dans la doctrine Symfony

        // $this->assertIsArray($parsed, 'Mauvais format de retour.');
        // $this->assertArrayHasKey('action', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('gameKey', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('playerKey', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('x', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('y', $parsed, 'Clé manquante.');
        // $this->assertSame('vote', $parsed['action']);
        // $this->assertSame(TestFixtures::PlayerKey1, $parsed['playerKey'], 'Mauvaise donnée de retour.');
        
        // Assert data
        $this->assertSame(0, $gp->getX());
        $this->assertSame(2, $gp->getY());
    }

    public function testVoteAndReturnCard()
    {
        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => TestFixtures::PlayerKey1,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $card = $this->entityManager
            ->getRepository(Card::class)
            ->findOneBy(['x' => '0', 'y' => '2'])
        ;

        $this->assertFalse($card->getReturned());

        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => TestFixtures::PlayerKey3,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);
        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => TestFixtures::PlayerKey4,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $card = $this->entityManager
            ->getRepository(Card::class)
            ->findOneBy(['x' => '0', 'y' => '2'])
        ;
        $game = $this->entityManager
            ->getRepository(Game::class)
            ->findOneBy(['publicKey' => TestFixtures::GameKey1]);
        $gamePlayers = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findBy(['game' => $game->getId()]);

        $this->assertTrue($card->getReturned());
        foreach($gamePlayers as $gp) {
            $this->assertSame(null, $gp->getX());
            $this->assertSame(null, $gp->getY());
        }
    }

    public function testStartGameNominal() {
        $this->service->startGame([
            'clients' => new SplObjectStorage(),
            'gameKey' => TestFixtures::GameKey1,
            'players' => [
                [
                    'playerKey' => TestFixtures::PlayerKey1,
                    'team' => 2,
                    'role' => 1
                ],
                [
                    'playerKey' => TestFixtures::PlayerKey2,
                    'team' => 1,
                    'role' => 2
                ]
            ],
        ]);

        $gp1 = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findOneBy(['publicKey' => TestFixtures::PlayerKey1])
        ;

        $gp2 = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findOneBy(['publicKey' => TestFixtures::PlayerKey2])
        ;

        $game = $this->entityManager
            ->getRepository(Game::class)
            ->findOneBy(['publicKey' => TestFixtures::GameKey1])
        ;

        $this->assertSame(GameStatus::OnGoing, $game->getStatus());
        $this->assertSame(TestFixtures::GameKey1, $gp1->getGame()->getPublicKey());
        $this->assertSame(2, $gp1->getTeam());
        $this->assertSame(1, $gp1->getRole());
        $this->assertSame(TestFixtures::GameKey1, $gp2->getGame()->getPublicKey());
        $this->assertSame(1, $gp2->getTeam());
        $this->assertSame(2, $gp2->getRole());
    }

    public function testNextTurnNominal() {
        $this->assertSame(Teams::Blue, 
                $this->entityManager
                ->getRepository(Game::class)
                ->findOneBy(['publicKey' => TestFixtures::GameKey1])
                ->getCurrentTeam());

        $this->service->passTurn([
            'clients' => new SplObjectStorage(),
            'playerKey' => TestFixtures::PlayerKey3,
            'gameKey' => TestFixtures::GameKey1,
            'from' => null
        ]);

        $this->assertSame(Teams::Red, 
                $this->entityManager
                ->getRepository(Game::class)
                ->findOneBy(['publicKey' => TestFixtures::GameKey1])
                ->getCurrentTeam());
    }

    public function testNextTurnWithVotes() 
    {
        $this->assertSame(Teams::Blue, 
                $this->entityManager
                ->getRepository(Game::class)
                ->findOneBy(['publicKey' => TestFixtures::GameKey1])
                ->getCurrentTeam());

        $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => TestFixtures::PlayerKey3,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);
        $this->service->passTurn([
            'clients' => new SplObjectStorage(),
            'playerKey' => TestFixtures::PlayerKey2,
            'gameKey' => TestFixtures::GameKey1,
            'from' => null
        ]);

        $this->assertSame(Teams::Red, 
                $this->entityManager
                ->getRepository(Game::class)
                ->findOneBy(['publicKey' => TestFixtures::GameKey1])
                ->getCurrentTeam());
        foreach($this->entityManager->getRepository(GamePlayer::class)->findBy(['game' => TestFixtures::GameKey1]) as $gp)
        {
            $this->assertNull($gp->getX());
            $this->assertNull($gp->getY());
        }
    }

}