<?php

// src/Command/CreateUserCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreReleasesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:store-releases';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i = 0;
        while (true) {
            print($i . "\n");
//            file_put_contents("/tmp/store-releases.txt", $i);
            sleep(60);
            $i++;
        }
    }
}
