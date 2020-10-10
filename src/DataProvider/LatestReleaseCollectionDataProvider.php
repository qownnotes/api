<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\LatestRelease;
use App\Service\ReleaseApi;

final class LatestReleaseCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(ReleaseApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return LatestRelease::class === $resourceClass;
    }

    /**
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): \Generator
    {
        $api = $this->api;
        $latestReleases = $api->fetchLatestReleases();

        foreach($latestReleases as $latestRelease) {
            yield $latestRelease;
        }
    }
}
