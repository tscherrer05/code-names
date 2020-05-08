<?php
namespace App\CodeNames;

abstract class GameStatus
{
    const Lobby = 0;
    const OnGoing = 1;
    const Finished = 2;
}