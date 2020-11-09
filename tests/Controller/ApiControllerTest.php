<?php

use App\Controller\DefaultController;
use App\DataFixtures\TestFixtures;
use App\Entity\Teams;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{

    public function testCardsEndPointNominal()
    {
        $client = static::createClient();

        $client->request('GET', '/cards?gameKey='.TestFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame('orange', $content[0]['word']);
    }

    public function testGameInfosWithSpy()
    {
        $client = static::createClient(); // à invoquer avant d'écrire dans la session
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, TestFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, TestFixtures::PlayerKey1);

        $client->request('GET', '/gameInfos?gameKey='.TestFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame(TestFixtures::GameKey1, $content['gameKey']);
        $this->assertSame(Teams::Blue, $content['currentTeam']);
        $this->assertSame('Acme', $content['currentWord']);
        $this->assertSame(42, $content['currentNumber']);
        $this->assertSame(1, $content['playerTeam']);
        $this->assertSame([
            TestFixtures::PlayerKey1, 
            TestFixtures::PlayerKey3,
            TestFixtures::PlayerKey4], 
            $content['remainingVotes']);
        $this->assertSame([], $content['currentVotes']);
        $this->assertSame(false, $content['canPassTurn']);
    }

    public function testGameInfosWithMasterSpyOfCurrentTeam() 
    {
        $client = static::createClient(); // à invoquer avant d'écrire dans la session
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, TestFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, TestFixtures::PlayerKey2);

        $client->request('GET', '/gameInfos?gameKey='.TestFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame(TestFixtures::GameKey1, $content['gameKey']);
        $this->assertSame(Teams::Blue, $content['currentTeam']);
        $this->assertSame('Acme', $content['currentWord']);
        $this->assertSame(42, $content['currentNumber']);
        $this->assertSame(1, $content['playerTeam']);
        $this->assertSame([
            TestFixtures::PlayerKey1, 
            TestFixtures::PlayerKey3,
            TestFixtures::PlayerKey4], 
            $content['remainingVotes']);
        $this->assertSame([], $content['currentVotes']);
        $this->assertSame(true, $content['canPassTurn']);
    }

    public function testGameInfosWithMasterSpyOfOppositeTeam()
    {
        $client = static::createClient(); // à invoquer avant d'écrire dans la session
        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, TestFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, TestFixtures::PlayerKey9);

        $client->request('GET', '/gameInfos?gameKey='.TestFixtures::GameKey1);

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame(TestFixtures::GameKey1, $content['gameKey']);
        $this->assertSame(Teams::Blue, $content['currentTeam']);
        $this->assertSame('Acme', $content['currentWord']);
        $this->assertSame(42, $content['currentNumber']);
        $this->assertSame(Teams::Red, $content['playerTeam']);
        $this->assertSame([
            TestFixtures::PlayerKey1, 
            TestFixtures::PlayerKey3,
            TestFixtures::PlayerKey4], 
            $content['remainingVotes']);
        $this->assertSame([], $content['currentVotes']);
        $this->assertSame(false, $content['canPassTurn']);
    }

    public function testGameInfosWithVotes()
    {
        $client = static::createClient(); // à invoquer avant d'écrire dans la session

        // gets the special container that allows fetching private services
        $container = self::$container;
        // now we can instantiate our service
        $this->service = $container->get('realtime');

        $session = static::$container->get('session');
        $session->set(DefaultController::GameSession, TestFixtures::GameKey1);
        $session->set(DefaultController::PlayerSession, TestFixtures::PlayerKey1);

        // TODO : Pouvoir organiser facilement les données de test
        $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => TestFixtures::PlayerKey3,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $this->service->vote([
            'x' => 0,
            'y' => 3,
            'playerKey' => TestFixtures::PlayerKey1,
            'gameKey' => TestFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $client->request('GET', '/gameInfos?gameKey='.TestFixtures::GameKey1);
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertSame(TestFixtures::GameKey1, $content['gameKey']);
        $this->assertSame(1, $content['currentTeam']);
        $this->assertSame('Acme', $content['currentWord']);
        $this->assertSame(42, $content['currentNumber']);
        $this->assertSame(1, $content['playerTeam']);
        $this->assertSame([
            TestFixtures::PlayerKey4
        ], 
        $content['remainingVotes']);
        $this->assertSame([
            TestFixtures::PlayerKey1 => "03",
            TestFixtures::PlayerKey3 => "02"
        ], $content['currentVotes']);
    }

}