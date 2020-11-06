<?php
namespace App\Tests\Controller;

use App\Controller\DefaultController;
use App\DataFixtures\TestFixtures;
use App\Entity\Game;
use App\Entity\GamePlayer;
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
    }

    public function testLoginPageNominal()
    {

        $this->client->request('GET', '/login');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('input#login');
    }

    public function testStartNominal()
    {
        $crawler = $this->client->request('GET', '/start');

        $this->assertSelectorExists('input#gameKey');
        $form = $crawler->selectButton('join-game')->form();

        $form['gameKey'] = TestFixtures::GameKey1;

        $crawler = $this->client->submit($form);
        $this->client->followRedirects(true);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertContains('join', $this->client->getRequest()->getUri());
        $this->client->followRedirect();
        $this->assertContains('login', $this->client->getRequest()->getUri());
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

    private function assertNumberOfGamePlayers(int $expected)
    {
        $actual = (int)$this->countGamePlayers();
        $this->assertSame($expected, $actual);
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