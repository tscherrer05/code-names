<?php

use App\Controller\DefaultController;
use App\DataFixtures\DefaultFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    public function testCardsEndPointNominal()
    {
        $client = static::createClient();

        $client->request('GET', '/cards?gameKey='.DefaultFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame('orange', $content[0]['word']);
    }

    public function testGameInfosEndPointNominal()
    {
        $client = static::createClient(); // à invoquer avant d'écrire dans la session
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, DefaultFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, DefaultFixtures::PlayerKey1);


        $client->request('GET', '/gameInfos?gameKey='.DefaultFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame(DefaultFixtures::GameKey1, $content['gameKey']);
        $this->assertSame(1, $content['currentTeam']);
        $this->assertSame('Acme', $content['currentWord']);
        $this->assertSame(42, $content['currentNumber']);
        $this->assertSame(1, $content['playerTeam']);
    }
}