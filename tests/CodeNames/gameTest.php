<?php
use PHPUnit\Framework\TestCase;
use App\CodeNames\Board          as Board;
use App\CodeNames\Card;
use App\CodeNames\GameInfo       as GameInfo;
use App\CodeNames\Player         as Player;
use App\Tests\CodeNames\TestData as TestData;
use Ramsey\Uuid\Nonstandard\Uuid;


class GameTest extends TestCase
{

    const BLUE = 1;
    const RED = 2;
    const MASTER = 1;
    const SPY = 2;

    public function testPlayerVoteFirstCard()
    {
        // Arrange
        $cards    = TestData::getCards();
        $board    = new Board($cards);
        $player1  = new Player(1, "nom", 1, 1);
        $player2  = new Player(2, "nom", 1, 1);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, 1, "", 0, array($player1, $player2));

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
        $player1  = new Player(1, "nom", 1, 1);
        $player2  = new Player(2, "nom", 1, 1);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, 1, "", 1, array($player1, $player2));

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
        $player1  = new Player(1, "Jack", 1, 1);
        $player2  = new Player(2, "Boby", 1, 1);
        $player3  = new Player(3, "Boby", 1, 1);
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
        $player = new Player(1, "Jack", 2, 1);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player));
        $coordX = 1;
        $coordY = 1;

        // Act
        $this->expectException(\InvalidArgumentException::class);
        $gameInfo->vote($player, $coordX, $coordY);
    }

    public function testVoteInvalidRanges()
    {
        // Arrange
        $board    = new Board(TestData::getCards());
        $player   = new Player(1, "Jack", 2, 1);
        $gameInfo = new GameInfo($board, 1, "", 1, array($player));
        $coordX = 5;
        $coordY = 5;

        // Act & assert
        $this->expectException(\InvalidArgumentException::class);
        $gameInfo->vote($player, $coordX, $coordY);
    }

    public function testCardShouldReturnWhenAllPlayersVoted()
    {
        // Arrange
        $board    = new Board(TestData::getCards());
        $player1  = new Player(1, "Jack", 1, 1);
        $player2  = new Player(2, "Boby", 1, 1);
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

    public function testVictory()
    {
        $board    = new Board(TestData::getCardAlmostWin());
        $player1  = new Player(1, "Jack", 1, 1);
        $player2  = new Player(2, "Boby", 1, 1);
        $player1->guid = Uuid::uuid1()->toString();
        $player2->guid = Uuid::uuid1()->toString();
        $gameInfo = new GameInfo($board, 1, "", 1, array($player1, $player2));
        $x = 4;
        $y = 0;

        $board->nbColorCards[1] = 1;
        $gameInfo->vote($player1, $x, $y);
        $this->assertSame(null, $gameInfo->winner($board));

        $gameInfo->vote($player2, $x, $y);
        $this->assertSame(1, $gameInfo->winner($board));
    }

    public function testAddSpy()
    {
        $name = "ChuckNorris23";
        $team = GameTest::RED;
        $role = GameTest::MASTER;
        $board    = new Board(TestData::getCards());
        $gameInfo = new GameInfo($board, 1, "", 1, array());

        $gameInfo->addPlayer($name, $team, $role);

        $this->assertSame(1, $gameInfo->nbPlayers());
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