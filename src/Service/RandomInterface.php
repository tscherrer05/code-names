<?php

namespace App\Service;

interface RandomInterface 
{

    function rand($min = null, $max = null): int;
    /**
     * Returns a random name from game's default list.
     */
    function name($excludedNames = []): string;

    /**
     * Returns a random word from game's default dictionnary.
     */
    function word($excludedWords = []): string;
}