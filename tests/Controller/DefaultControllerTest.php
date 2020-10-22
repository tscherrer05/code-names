<?php
namespace App\Tests\Controller;

use App\Controller\DefaultController;
use App\DataFixtures\DefaultFixtures;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $gamePlayerRepository;
    private $client;

    public function setUp() :void
    {
        $this->client = static::createClient();
        $this->gamePlayerRepository = static::$container->get('doctrine')
        ->getManager()
        ->getRepository(GamePlayer::class);
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

        $form['gameKey'] = DefaultFixtures::GameKey1;

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
        $session->set(DefaultController::GameSession, DefaultFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, DefaultFixtures::PlayerKey1);

        $this->client->request('GET', '/lobby');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('#game-key');
    }

    public function testAutoConnectNominal() 
    {
        $before = $this->countGamePlayers();

        $this->client->request('GET', '/autoConnect?gameKey='.DefaultFixtures::GameKey1);

        $this->assertNumberOfGamePlayers($before+1);

        $this->client->followRedirect();
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertContains('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    public function testAutoConnectTwice() 
    {
        $before = $this->countGamePlayers();
        static::$container->get('session')->set(DefaultController::PlayerSession, DefaultFixtures::PlayerKey1);
        $before = $this->countGamePlayers();
        $this->client->request('GET', '/autoConnect?gameKey='.DefaultFixtures::GameKey1);
        $this->assertNumberOfGamePlayers($before);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertContains('game', $this->client->getRequest()->getUri(), "Doit être sur la page game");
    }

    public function testAutoConnectWithMissingSpyMaster() 
    {
        $session = static::$container->get('session');
        
        // Connexion auto
        $this->client->request('GET', '/autoConnect?gameKey='.DefaultFixtures::GameKey1);

        // Vérifier que le MasterSpy de l'autre équipe est ajouté
        $masterSpies = intval($this->gamePlayerRepository
                ->createQueryBuilder('gp')
                ->where('gp.role = '.Roles::Spy)
                ->select('count(gp.id)')
                ->getQuery()
                ->getSingleScalarResult());
        $this->assertSame(2, $masterSpies);
    }

    private function assertNumberOfGamePlayers(int $expected)
    {
        $this->assertSame($expected, (int)$this->countGamePlayers());
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