<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Entity\AppRelease;
use App\Service\ReleaseApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:store-releases',
    description: 'Fetches the latest release from GitHub and stores a new app release'
)]
class StoreReleasesCommand extends Command
{
    /**
     * @var ReleaseApi
     */
    private $api;

    public function __construct(ReleaseApi $api)
    {
        $this->api = $api;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // we'll kill this script after 60 iterations and let supervisord start it again
        // in case it hogged memory
        $killCount = 0;
        while (++$killCount < 60) {
            $latestRelease = $this->api->fetchLatestRelease('linux');

            $version = $latestRelease->getVersion();
            $output->writeln("Found version '$version'");
            $appRelease = $this->api->storeAppReleaseIfNotExists(
                $version,
                $latestRelease->getReleaseChangesMarkdown(),
                $latestRelease->getDateCreated()
            );

            if ($appRelease instanceof AppRelease) {
                $id = $appRelease->getId();
                $output->writeln("A new app release was created (id: $id)");
            } else {
                $output->writeln('App release already existed');
            }

            sleep(60);
        }

        return Command::SUCCESS;
    }
}
