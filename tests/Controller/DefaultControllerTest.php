<?php
namespace App\Tests\Controller;

use App\CodeNames\GameStatus;
use App\Controller\DefaultController;
use App\DataFixtures\TestFixtures;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Service\Random;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $gamePlayerRepository;
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

        $this->assertContains('lobby', $this->client->getRequest()->getUri());
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
        $this->assertContains('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    public function testFirstAutoConnect()
    {
        $before = intval($this->countGamePlayers());
        $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey3);

        $this->assertNumberOfGamePlayers($before+1);
    }

    public function testAutoConnectTwice() 
    {
        $before = $this->countGamePlayers();
        static::$container->get('session')->set(DefaultController::PlayerSession, TestFixtures::PlayerKey1);
        $before = $this->countGamePlayers();
        $this->client->request('GET', '/autoConnect?gameKey='.TestFixtures::GameKey2);
        $this->assertNumberOfGamePlayers(intval($before));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertContains('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    // TODO : how to mock services in functional tests
    // public function testAutoConnectUniqueName() 
    // {
    //     $randomService = $this->createMock(Random::class);
    //     $randomService->expects($this->any())
    //         ->method('name')
    //         ->willReturn('NomUnique');
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
        $this->assertContains('autoConnect', $this->client->getRequest()->getUri(), "Doit être sur la page autoConnect.");

        $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('game', $this->client->getRequest()->getUri(), "Doit être sur la page game.");

        $game = $this->gameRepository->createQueryBuilder('g')->select('g')->orderBy('g.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();

        $this->assertNotNull($game);
        $this->assertEquals(GameStatus::OnGoing, $game->getStatus());
        $this->assertEquals(1, \count($game->getGamePlayers()));
        $this->assertEquals(Roles::Master, $game->getGamePlayers()[0]->getRole());

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