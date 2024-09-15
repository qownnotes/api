<?php
/**
 * LatestRelease data provider
 */

declare(strict_types=1);

namespace App\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\LatestRelease;
use App\Service\ReleaseApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class LatestReleaseProvider extends AbstractController implements ProviderInterface
{
    private $api;

    public function __construct(ReleaseApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return LatestRelease[]|LatestRelease|null
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $latestReleases = $this->api->fetchLatestReleases();

            return $latestReleases;
        } else {
            $id = $uriVariables['identifier'];
            assert(is_string($id));
            $filters = $context['filters'] ?? [];

            return $this->api->fetchLatestRelease($id, $filters);
        }
    }
}
