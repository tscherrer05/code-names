<?php
namespace App\Tests\Controller;

use App\CodeNames\GameStatus;
use App\Controller\RealTimeController;
use App\DataFixtures\TestFixtures;
use App\Entity\Card;
use App\Entity\Colors;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Teams;
use PHPUnit\Framework\MockObject\MockObject;
use SplObjectStorage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ratchet\ConnectionInterface;

class RealTimeControllerTest extends WebTestCase
{
    private RealTimeController $service;
    private MockObject $client1;
    private MockObject $client2;
    private MockObject $client3;
    private \SplObjectStorage $clients;
    
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

        $this->client1 = $this->getFakeClient(TestFixtures::PlayerConnectionId1);
        $this->client2 = $this->getFakeClient(TestFixtures::PlayerConnectionId2);
        $this->client3 = $this->getFakeClient(9999);
        $this->clients = new SplObjectStorage();
        $this->clients->attach($this->client1);
        $this->clients->attach($this->client2);
        $this->clients->attach($this->client3);
    }

    public function testVoteNominal()
    {
        // Arrange
        $model = [
            'action'    => 'hasVoted',
            'playerKey' => TestFixtures::PlayerKey1,
            'playerName' => 'Spy' . TestFixtures::PlayerKey1,
            'x'         => 0,
            'y'         => 2,
            'color'     => Colors::Red
        ];
        $params = json_encode($model);
        $playerKey = TestFixtures::PlayerKey1;

        // Act
        $this->client1->expects($this->once())->method('send');
        $this->client1->expects($this->once())->method('send')->with($params);
        $this->client2->expects($this->once())->method('send');
        $this->client2->expects($this->once())->method('send')->with($params);
        $this->client3->expects($this->never())->method('send');
        $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => $playerKey,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => $this->clients,
            'from' => null
        ]);
        
        // Assert
        $gp = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findOneBy(['publicKey' => $playerKey])
        ;
        $this->assertSame(0, $gp->getX());
        $this->assertSame(2, $gp->getY());
    }

    public function testVoteAndReturnCard()
    {
        $this->service->vote([
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
        
        $game = $this->getGame(TestFixtures::GameKey1);
        $this->assertSame(Teams::Red, $game->getCurrentTeam());
        $gamePlayers = $game->getGamePlayers();
        foreach($gamePlayers as $gp)
        {
            $this->assertNull($gp->getX());
            $this->assertNull($gp->getY());
        }
    }

    public function testResetGame_AllCardsHidden() {
        $gameKey = TestFixtures::GameKey4;
        $this->service->resetGame([
            'gameKey' => $gameKey,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $game = $this->getGame($gameKey);
        
        $cards = $game->getCards()->toArray();
        $this->assertTrue(\count($cards) > 0);
        foreach($cards as $c) {
            $this->assertFalse($c->getReturned());
        }
    }

    public function testResetGame_AllVotesReset() {
        $gameKey = TestFixtures::GameKey4;
        $this->service->resetGame([
            'gameKey' => $gameKey,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $game = $this->getGame($gameKey);
        
        $gps = $game->getGamePlayers()->toArray();
        $this->assertTrue(\count($gps) > 0);
        foreach($gps as $gp) {
            $this->assertNull($gp->getX());
            $this->assertNull($gp->getY());
        }
    }

    public function testResetGame_OneBlackCard() {
        // TODO : mock rand

        $gameKey = TestFixtures::GameKey4;
        $this->service->resetGame([
            'gameKey' => $gameKey,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $game = $this->getGame($gameKey);
        $cards = $game->getCards()->toArray();
        $this->assertTrue(\count($cards) > 0);
        $count = 0;
        foreach($cards as $c) {
            if($c->getColor() === Colors::Black) $count++;
        }
        // $this->assertSame(1, $count);
    }

    public function testEmptyGame_Nominal() {
        $gameKey = TestFixtures::GameKey1;
        $this->service->emptyGame([
            'gameKey' => $gameKey,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $this->assertSame(0, \count($this->getGame($gameKey)->getGamePlayers()->toArray()));
    }

    public function testConnectPlayer()
    {
        // Arrange
        $gameKey = TestFixtures::GameKey1;
        $playerKey = TestFixtures::PlayerKey1;
        $gp = $this->getGamePlayer($playerKey);
        $this->assertNull($gp->getConnectionId());

        // Act
        $this->service->connectPlayer([
            'gameKey' => $gameKey,
            'playerKey' => $playerKey,
            'clients' => new SplObjectStorage(),
            'from' => $this->client1
        ]);

        // Assert
        $gp = $this->getGamePlayer($playerKey);
        $this->assertSame($this->client1->resourceId, $gp->getConnectionId());
    }

    private function getGame($gameKey) {
        return $this->entityManager
                    ->getRepository(Game::class)
                    ->findOneBy(['publicKey' => $gameKey]);
    }

    private function getGamePlayer($playerKey) {
        return $this->entityManager
                    ->getRepository(GamePlayer::class)
                    ->findOneBy(['publicKey' => $playerKey]);
    }

    private function getFakeClient($resourceId)
    {
        $mock = $this->getMockBuilder(ConnectionInterface::class)
            ->setMockClassName('FakeConnection')
            ->onlyMethods(['send', 'close'])
            ->getMock();
        $mock->resourceId = $resourceId;
        return $mock;
    }

}