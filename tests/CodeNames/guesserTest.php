<?php
use PHPUnit\Framework\TestCase;
use App\CodeNames\Board as Board;
use App\CodeNames\Guesser as Guesser;
use App\CodeNames\GameInfo as GameInfo;
use App\Tests\CodeNames\TestData as TestData;


class GuesserTest extends TestCase
{
    public function testAnnounceWordNumber()
    {
        // Arrange
        $board = new Board(TestData::getCards());
        $guesser1 = new Guesser("toto", 1);
        $gameInfo = new GameInfo($board, 1, "", 1, array());

        // Act
        $guesser1->announce($gameInfo, $board, "word", 3);

        // Assert
        $this->assertSame($gameInfo->currentWord(), "word");
        $this->assertSame($gameInfo->currentNumber(), 3);
        $this->assertSame($gameInfo->currentTeam(), 1);
    }

    public function testAnnounceTwiceShouldfail()
    {
        // Arrange
        $board = new Board(TestData::getCards());
        $guesser1 = new Guesser("toto", 1);
        $gameInfo = new GameInfo($board, 1, "", 1, array());

        // Act & assert
        $guesser1->announce($gameInfo, $board, "word", 3);
        $this->expectException(\Exception::class);
        $guesser1->announce($gameInfo, $board, "otherword", 2);

    }
}

?>