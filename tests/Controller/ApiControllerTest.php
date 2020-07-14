<?php

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
// TODO : trouver comment mock les sessions

        // $client = static::createClient();

        // $client->request('GET', '/gameInfos?gameKey='.DefaultFixtures::GameKey1);

        // $response = $client->getResponse();

        // $this->assertSame(200, $response->getStatusCode());

        // $content = json_decode($response->getContent(), true);

        // $this->assertSame(DefaultFixtures::GameKey1, $content['gameKey']);
        // $this->assertSame(1, $content['currentTeam']);
        // $this->assertSame('Acme', $content['currentWord']);
        // $this->assertSame(42, $content['currentNumber']);
        // $this->assertSame(1, $content['team']);
    }
}