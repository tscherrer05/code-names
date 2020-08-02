<?php
namespace App\Tests\Controller;

use App\Controller\DefaultController;
use App\DataFixtures\DefaultFixtures;
use App\Entity\GamePlayer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{

    public function testLoginPageNominal()
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('input#login');
    }

    public function testStartNominal()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/start');

        $this->assertSelectorExists('input#gameKey');
        $form = $crawler->selectButton('join-game')->form();

        $form['gameKey'] = DefaultFixtures::GameKey1;

        $crawler = $client->submit($form);
        $client->followRedirects(true);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertContains('join', $client->getRequest()->getUri());
        $client->followRedirect();
        $this->assertContains('login', $client->getRequest()->getUri());
    }

    public function testRefreshLobby()
    {
        $client = static::createClient();
        $session = static::$container->get('session');
        $session->set(DefaultController::PlayerSession, 1);
        
        $client->request('GET', '/refreshLobby');

        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isRedirect(), "Refresh lobby doit rediriger vers le lobby.");

        $this->assertContains('lobby', $client->getRequest()->getUri());
    }

    public function testLobbyNominal()
    {
        $client = static::createClient();
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, DefaultFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, DefaultFixtures::PlayerKey1);

        $client->request('GET', '/lobby');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('#game-key');
    }

    public function testAutoConnectNominal() 
    {
        $client = static::createClient();

        $before = $this->countGamePlayers();

        $client->request('GET', '/autoConnect?gameKey='.DefaultFixtures::GameKey1);

        $this->assertNumberOfGamePlayers($before+1);

        $client->followRedirect();
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertContains('game', $client->getRequest()->getUri(), "Doit être sur la page game");
    }

    public function testAutoConnectTwice() 
    {
        $client = static::createClient();
        $before = $this->countGamePlayers();
        $session = static::$container->get('session');
        $session->set(DefaultController::PlayerSession, DefaultFixtures::PlayerKey1);
        $before = $this->countGamePlayers();
        $client->request('GET', '/autoConnect?gameKey='.DefaultFixtures::GameKey1);
        $this->assertNumberOfGamePlayers($before);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect(), "Doit rediriger.");
        $this->assertContains('game', $client->getRequest()->getUri(), "Doit être sur la page game");
    }

    private function assertNumberOfGamePlayers(int $expected)
    {
        $this->assertSame($expected, (int)$this->countGamePlayers());
    }

    private function countGamePlayers()
    {
        $em = static::$container->get('doctrine')->getManager();
        return $em
            ->getRepository(GamePlayer::class)
            ->createQueryBuilder('gp')
            ->select('count(gp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}