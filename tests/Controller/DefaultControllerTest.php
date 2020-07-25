<?php
namespace App\Tests\Controller;

use App\Controller\DefaultController;
use App\DataFixtures\DefaultFixtures;
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
        $session->set(DefaultController::PlayerSession, 3);

        $client->request('GET', '/lobby');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('#game-key');
    }

    // TODO : warning : writes in datasource
    // public function testConnect()
    // {
    //     $client = static::createClient();

    //     // $client->request(
    //     //     'POST', 
    //     //     '/login',
    //     //     [
    //     //         'gameId' => 1,
    //     //         'login' => "ChuckNorris78",
    //     //         'team' => 1,
    //     //         'role' => 2
    //     //     ]
    //     // );

    //     // $this->assertSame(302, $client->getResponse()->getStatusCode());
    // }
}