<?php
/**
 * LatestRelease item data provider
 */

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\LatestRelease;
use App\Service\ReleaseApi;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class LatestReleaseItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    private $requestStack;

    public function __construct(ReleaseApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return LatestRelease::class === $resourceClass;
    }

    /**
     * @param string $resourceClass
     * @param array|int|string $id
     * @param string|null $operationName
     * @param array $context
     * @return LatestRelease|null
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?LatestRelease
    {
        return $this->api->fetchLatestRelease($id);
    }
}
