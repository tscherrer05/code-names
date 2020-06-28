<?php
namespace App\Tests\Controller;

use App\Controller\RealTimeController;
use App\DataFixtures\DefaultFixtures;
use App\Entity\Card;
use App\Entity\GamePlayer;
use App\Entity\Player;
use SplObjectStorage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RealTimeControllerTest extends WebTestCase
{
    private RealTimeController $service;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function setUp()
    {
        // start the symfony kernel
        self::bootKernel();

        // gets the special container that allows fetching private services
        $container = self::$container;

        // now we can instantiate our service
        $this->service = $container->get('realtime');

        $this->entityManager = $container->get('doctrine')->getManager();
    }

    public function testVoteNominal()
    {
        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => DefaultFixtures::PlayerKey1,
            'gameKey' => DefaultFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $parsed = json_decode($result, true);

        $player = $this->entityManager
            ->getRepository(Player::class)
            ->findOneBy(['playerKey' => DefaultFixtures::PlayerKey1])
        ;
        $gp = $this->entityManager
            ->getRepository(GamePlayer::class)
            ->findOneBy(['player' => $player->getId()])
        ;

        // Assert retour
        // TODO : Trouver comment intercepter ces messages envoyés aux clients
        // Le mock n'est pas dans la doctrine Symfony

        // $this->assertIsArray($parsed, 'Mauvais format de retour.');
        // $this->assertArrayHasKey('action', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('gameKey', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('playerKey', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('x', $parsed, 'Clé manquante.');
        // $this->assertArrayHasKey('y', $parsed, 'Clé manquante.');
        // $this->assertSame('vote', $parsed['action']);
        // $this->assertSame(DefaultFixtures::PlayerKey1, $parsed['playerKey'], 'Mauvaise donnée de retour.');
        
        // Assert data
        $this->assertSame(0, $gp->getX());
        $this->assertSame(2, $gp->getY());
    }

    public function testVoteAndReturnCard()
    {
        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => DefaultFixtures::PlayerKey1,
            'gameKey' => DefaultFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $card = $this->entityManager
            ->getRepository(Card::class)
            ->findOneBy(['x' => '0', 'y' => '2'])
        ;

        $this->assertSame(false, $card->getReturned());

        $result = $this->service->vote([
            'x' => 0,
            'y' => 2,
            'playerKey' => DefaultFixtures::PlayerKey2,
            'gameKey' => DefaultFixtures::GameKey1,
            'clients' => new SplObjectStorage(),
            'from' => null
        ]);

        $parsed = json_decode($result, true);

        $card = $this->entityManager
            ->getRepository(Card::class)
            ->findOneBy(['x' => '0', 'y' => '2'])
        ;

        $this->assertSame(true, $card->getReturned());
    }


}