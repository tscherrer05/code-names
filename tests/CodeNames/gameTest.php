<?php
use PHPUnit\Framework\TestCase;
use App\CodeNames\Board          as Board;
use App\CodeNames\Card;
use App\CodeNames\GameInfo       as GameInfo;
use App\CodeNames\Player         as Player;
use App\Entity\Roles;
use App\Entity\Teams;
use App\Tests\CodeNames\TestData as TestData;
use Ramsey\Uuid\Nonstandard\Uuid;


class GameTest extends TestCase
{
    public function testPlayerVoteFirstCard()
    {
        // Arrange
        $cards    = TestData::getCards();
        $board    = new Board($cards);
        $player1  = new Player(1, "nom", Teams::Blue, Roles::Spy);
        $player2  = new Player(2, "nom", Teams::Blue, Roles::Spy);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, Teams::Blue, "", 0, array($player1, $player2));

        // Act
        $gameInfo->vote($player1, 1, 1);

        // Assert
        $this->assertSame(1, count($board->getVotes(1, 1)));
    }

    public function testPlayerVoteFirstCardThenAnother()
    {
        // Arrange
        $cards    = TestData::getCards();
        $board    = new Board($cards);
        $player1  = new Player(1, "nom", Teams::Blue, Roles::Spy);
        $player2  = new Player(2, "nom", Teams::Blue, Roles::Spy);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, Teams::Blue, "", 1, array($player1, $player2));

        // Act
        $gameInfo->vote($player1, 1, 1);

        // Assert
        $this->assertSame(1, count($board->getVotes(1, 1)));

        // Act
        $gameInfo->vote($player1, 1, 2);

        // Assert
        $this->assertSame(0, count($board->getVotes(1, 1)));
        $this->assertSame(1, count($board->getVotes(1, 2)));
    }

    public function testTwoPlayersVoteForOneCard()
    {
        // Arrange
        $player1  = new Player(1, "Jack", Teams::Blue, Roles::Spy);
        $player2  = new Player(2, "Boby", Teams::Blue, Roles::Spy);
        $player3  = new Player(3, "Boby", Teams::Blue, Roles::Spy);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $player3->guid = Uuid::uuid1()->toString();
        $votes[$player1->guid] = new Card('whatevs', 1, 0, 0);
        $board    = new Board(TestData::getCards(), $votes);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player1, $player2, $player3));
        $coordX = 3;
        $coordY = 3;

        // Act
        $gameInfo->vote($player1, $coordX, $coordY);
        $gameInfo->vote($player2, $coordX, $coordY);

        // Assert
        $this->assertSame(2, count($board->getVotes($coordX, $coordY)));
    }

    public function testPlayerVoteForOtherTeamVoteShouldFail()
    {
        // Arrange
        $board = new Board(TestData::getCards());
        $player = new Player(1, "Jack", Teams::Red, Roles::Spy);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player));
        $coordX = 1;
        $coordY = 1;

        // Act
        $result = $gameInfo->vote($player, $coordX, $coordY);

        $this->assertSame([
            'ok' => false
        ], $result);
    }

    public function testPlayerVoteForReturnedCardShouldFail()
    {
        // Arrange
        $team = 1;
        $board = new Board(TestData::getCardAlmostWin());
        $player = new Player(1, "Jack", Teams::Blue, Roles::Spy);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player));
        // Coordonnées d'une carte retournée
        $coordX = 0;
        $coordY = 0;

        // TODO ; retourner plutôt un objet resultant (succès/échec, messages d'erreur, objets résultants)
        $this->expectException(\Exception::class);
        $gameInfo->vote($player, $coordX, $coordY);

    }

    public function testVoteInvalidRanges()
    {
        // Arrange
        $board    = new Board(TestData::getCards());
        $player   = new Player(1, "Jack", Teams::Blue, Roles::Spy);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player));
        $coordX = 5;
        $coordY = 5;

        // Act & assert
        $this->expectException(\InvalidArgumentException::class);
        $result = $gameInfo->vote($player, $coordX, $coordY);
    }

    public function testCardShouldReturnWhenAllPlayersVoted()
    {
        // Arrange
        $board    = new Board(TestData::getCards());
        $player1  = new Player(1, "Jack", Teams::Blue, Roles::Spy);
        $player2  = new Player(2, "Boby", Teams::Blue, Roles::Spy);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, 1, "", 1, array($player1, $player2));
        $coordX = 3;
        $coordY = 3;

        // Act
        $gameInfo->vote($player1, $coordX, $coordY);
        $gameInfo->vote($player2, $coordX, $coordY);

        // Assert
        $this->assertTrue($board->isCardReturned($coordX, $coordY));
        $this->assertSame(count($board->getVotes($coordX, $coordY)), 0);
    }

    // public function testVictory()
    // {
    //     $board    = new Board(TestData::getCardAlmostWin());
    //     $player1  = new Player(Uuid::uuid1()->toString(), "Jack", Teams::Blue, Roles::Spy);
    //     $player2  = new Player(Uuid::uuid1()->toString(), "Boby", Teams::Blue, Roles::Spy);
    //     $gameInfo = new GameInfo($board, 1, "", 1, array($player1, $player2));
    //     $x = 4;
    //     $y = 0;

    //     $gameInfo->vote($player1, $x, $y);
    //     $this->assertSame(null, $gameInfo->winner($board));

    //     $gameInfo->vote($player2, $x, $y);
    //     $this->assertSame(1, $gameInfo->winner($board));
    // }

    public function testAddSpyNominal()
    {
        $name = "ChuckNorris23";
        $role = Roles::Spy;
        $team = Teams::Red;
        $board    = new Board(TestData::getCards());
        $gameInfo = new GameInfo($board, 1, "", 1, array());

        $gameInfo->addPlayer(Uuid::uuid1()->toString(), $name, $team, $role);

        $this->assertSame(1, $gameInfo->nbSpies());
    }

    public function testAddPlayerInvalid() 
    {
        $board    = new Board(TestData::getCards());
        $gameInfo = new GameInfo($board, 1, "", 1, array());

        // 2 Master spies
        $gameInfo->addPlayer(Uuid::uuid1()->toString(), "yesyes", Teams::Blue, Roles::Master);
        $gameInfo->addPlayer(Uuid::uuid1()->toString(), "yesyes", Teams::Red, Roles::Master);

        // Try to add another master spy
        $this->expectException(\Exception::class);
        $gameInfo->addPlayer(Uuid::uuid1()->toString(), "whatev", Teams::Blue, Roles::Master);
    }

    public function testGetPlayerById()
    {
        $board    = new Board(TestData::getCards());
        $player1  = new Player(3, "Jack", 1, 1);
        $playerGuid = Uuid::uuid1();
        $player1->guid = $playerGuid->toString();
        $gameInfo = new GameInfo($board, 1, "", 1, array($player1));

        $result = $gameInfo->getPlayer($playerGuid);

        $this->assertSame("Jack", $result->name);
    }

    public function testNextTurnNominal()
    {
        $board = new Board(TestData::getCards());
        $player = new Player(3, "Kirk", 1, 1);
        $player->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, 1, '', 1, array($player));
        $before = $gameInfo->currentTeam();

        $gameInfo->passTurn();

        $this->assertNotSame($before, $gameInfo->currentTeam());

        $gameInfo->passTurn();
        $this->assertSame($before, $gameInfo->currentTeam());
    }
}

?>