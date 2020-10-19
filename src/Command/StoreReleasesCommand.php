<?php

// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\AppRelease;
use App\Entity\LatestRelease;
use App\Service\ReleaseApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreReleasesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:store-releases';

    /**
     * @var ReleaseApi
     */
    private $api;

    public function __construct(ReleaseApi $api)
    {
        $this->api = $api;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Fetches the latest release from GitHub and stores a new app release')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            $latestRelease = $this->api->fetchLatestRelease("linux");

            $version = $latestRelease->getVersion();
            $output->writeln( "Found version '$version'" );
            $appRelease = $this->api->storeAppReleaseIfNotExists(
                $version,
                $latestRelease->getReleaseChangesMarkdown(),
                $latestRelease->getDateCreated()
            );

            if ($appRelease instanceof AppRelease)
            {
                $id = $appRelease->getId();
                $output->writeln( "A new app release was created (id: $id)" );
            }
            else
            {
                $output->writeln( "App release already existed" );
            }

            sleep(60);
        }
    }
}
