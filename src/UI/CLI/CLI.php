<?php

namespace TheDeceased\PHParser\UI\CLI;

use TheDeceased\PHParser\UI\CLI\Commands\ParseCommand;

class CLI
{
    public static function run()
    {
        $lastOptionIndex = 0;
        $options = getopt(ParseCommand::OPTIONS, ParseCommand::ARGUMENTS, $lastOptionIndex);
        (new ParseCommand(array_slice($_SERVER['argv'], $lastOptionIndex), $options))->run();
    }
}
