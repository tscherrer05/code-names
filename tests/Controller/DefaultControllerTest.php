<?php
namespace App\Tests\Controller;

use App\CodeNames\GameStatus;
use App\Controller\DefaultController;
use App\DataFixtures\TestFixtures;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class DefaultControllerTest extends WebTestCase
{
    private $gameRepository;
    private $client;

    public function setUp() :void
    {
        $this->client = static::createClient();
        $this->gamePlayerRepository = static::$container->get('doctrine')
            ->getManager()
            ->getRepository(GamePlayer::class);
        $this->gameRepository = static::$container->get('doctrine')
            ->getManager()
            ->getRepository(Game::class);
            // TODO : mock la classe random
    }

    public function testLoginPageNominal()
    {
        $this->client->request('GET', '/login');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('input#login');
    }

    public function testRefreshLobby()
    {
        $session = static::$container->get('session');
        $session->set(DefaultController::PlayerSession, 1);
        
        $this->client->request('GET', '/refreshLobby');

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isRedirect(), "Refresh lobby doit rediriger vers le lobby.");

        $this->assertStringContainsString('lobby', $this->client->getRequest()->getUri());
    }

    public function testLobbyNominal()
    {
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, TestFixtures::GameKey2);
        $session->set(DefaultController::PlayerSession, TestFixtures::PlayerKey7);

        $this->client->request('GET', '/lobby');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('#game-key');
    }

    public function testAutoConnectNominal() 
    {
        $before = $this->countGamePlayers();
        $this->assertNumberOfGamePlayers(intval($before));

        $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey2);

        $this->assertNumberOfGamePlayers(intval($before)+1);

        $this->client->followRedirect();
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertStringContainsString('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    public function testFirstAutoConnect()
    {
        // Assert guard
        $before = intval($this->countGamePlayers());

        // Arrange
        $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey3);

        // Assert
        $this->assertNumberOfGamePlayers($before+1);
    }

    public function testAutoConnectTwice() 
    {
        // Assert guard
        $before = $this->countGamePlayers();

        // Act
        static::$container->get('session')->set(DefaultController::PlayerSession, TestFixtures::PlayerKey1);
        
        // Assert
        $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey2);
        $this->assertNumberOfGamePlayers(intval($before));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertStringContainsString('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    // TODO : how to mock services in functional tests
    // public function testAutoConnectUniqueName() 
    // {
    //     $randomService = $this->createMock(Random::class, );
    //     $randomService->method('name')->willReturn('NomUnique');
    //     $this->assertEquals('dfssfqd', $randomService->name());
    // }

    // TODO : résoudre le mystère de ce bug
    // public function testAutoConnectWithMissingSpyMaster() 
    // {
    //     // Connexion auto
    //     $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey2);

    //     // Vérifier que le MasterSpy de l'autre équipe est ajouté
    //     $gamePlayers = static::$container->get('doctrine')->getManager()
    //                     ->getRepository(GamePlayer::class)
    //                     ->createQueryBuilder('gp')
    //                     ->getQuery()
    //                     ->getResult();

    //     $masterSpies = intval(
    //         array_filter(
    //             $gamePlayers,
    //             function($gp) {
    //                 return $gp->getRole() === Roles::Master
    //                         && $gp->getGame()->getPublicKey() === TestFixtures::GameKey2;
    //             }
    //         )
    //     );
    //     $this->assertSame(2, $masterSpies, 'Autoconnect should prioritize MasterSpies');
    // }


    public function testCreateGame()
    {
        // Arrange
        $before = $this->countGames();

        // Act
        $this->client->request('GET', '/createGame');

        // Assert
        $this->assertEquals($before + 1, $this->countGames());

        $this->client->followRedirect();
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('autoConnect', $this->client->getRequest()->getUri(), "Doit être sur la page autoConnect.");

        $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('game', $this->client->getRequest()->getUri(), "Doit être sur la page game.");

        $game = $this->gameRepository->createQueryBuilder('g')
            ->select('g')
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotNull($game);
        $this->assertEquals(GameStatus::OnGoing, $game->getStatus());
        $this->assertCount(1, $game->getGamePlayers());
        $cards = $game->getCards()->toArray();
        $this->assertCount(25, $cards);
        $this->assertEquals(Roles::Master, $game->getGamePlayers()[0]->getRole());
    }

    /**
     * @dataProvider invalidInputsProvider
     */
    public function testJoinAutoConnect_invalidParameters($invalidGameKey) 
    {
        // Act
        $crawler = $this->client->request('POST', '/joinAutoConnect', ['gameKey' => $invalidGameKey]);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('joinAutoConnect', $this->client->getRequest()->getUri(), "Doit être sur la page joinAutoConnect.");
        $this->assertSelectorTextContains('#gameKeyError', 'Clé invalide');
    }

    public function invalidInputsProvider()
    {
        return [
            [''],
            ['sdfmsldfkjsdmf'],
            ['0980943042'],
            ['sfdsd-987987'],
            [Uuid::uuid1()->toString()]
        ];
    }

    /**
     * @dataProvider sessionDataProvider
     */
    public function testJoinAutoConnect_nominal($gameKey, $playerKey)
    {
        // Arrange
        $expectedRedirect = 'autoConnect';

        // Act
        $this->client->request('POST', '/joinAutoConnect', ['gameKey' => $gameKey]);

        // Assert
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->client->followRedirect();
        $this->assertStringContainsString($expectedRedirect, $this->client->getRequest()->getUri(), 'Doit être sur la page '.$expectedRedirect);
        $this->assertStringContainsString($gameKey, $this->client->getRequest()->getUri(), "Doit contenir la clé du jeu.");
    }

    public function sessionDataProvider()
    {
        return [
            [TestFixtures::GameKey1, null],
            [TestFixtures::GameKey1, Uuid::uuid1()->toString()],
        ];
    }

    public function testAutoConnect_alreadyInGame()
    {
        // Arrange
        $gameKey = TestFixtures::GameKey1;
        $playerKey = TestFixtures::PlayerKey3;
        $session = new Session(new MockArraySessionStorage());
        $session->set(DefaultController::PlayerSession, $playerKey);
        $session->set(DefaultController::GameSession, $gameKey);
        static::$container->set('session', $session);
        $expectedRedirect = 'alreadyInGame';

        // Act
        $this->client->followRedirects(true);
        $this->client->request('GET', '/autoConnect', ['gameKey' => $gameKey]);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($expectedRedirect, $this->client->getRequest()->getUri(), "Doit être sur la page ".$expectedRedirect);
        $this->assertSelectorExists('#message');
    }

    public function testAutoConnect_createGame()
    {
        // Arrange
        $session = static::$container->get('session');
        $gameKey = TestFixtures::GameKey1;
        $playerKey = TestFixtures::PlayerKey3;
        $session->set(DefaultController::PlayerSession, $playerKey);
        $session->set(DefaultController::GameSession, $gameKey);
        $expectedRedirect = 'alreadyInGame';

        // Act
        $this->client->followRedirects(true);
        $this->client->request('GET', '/createGame', ['gameKey' => $gameKey]);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($expectedRedirect, $this->client->getRequest()->getUri(), "Doit être sur la page ".$expectedRedirect);
        $this->assertSelectorExists('#message');
        
    }

    private function assertNumberOfGamePlayers(int $expected)
    {
        $actual = (int)$this->countGamePlayers();
        $this->assertSame($expected, $actual);
    }

    private function countGames() 
    {
        return $this->gameRepository->createQueryBuilder('g')
        ->select('count(g.id)')
        ->getQuery()
        ->getSingleScalarResult();
    }

    private function countGamePlayers()
    {
        return static::$container->get('doctrine')->getManager()
            ->getRepository(GamePlayer::class)
            ->createQueryBuilder('gp')
            ->select('count(gp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}