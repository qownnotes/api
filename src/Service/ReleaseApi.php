<?php

declare(strict_types=1);
/**
 * Release API service.
 */

namespace App\Service;

use App\Entity\LatestRelease;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use JsonException;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use League\Uri\Contracts\UriException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReleaseApi
{
    const RELEASE_CACHE_TTL = 60;

    private $clientHandler;

    private $container;

    /**
     * @var ReleaseUrlApi
     */
    private $urls;

    /**
     * @var FilesystemAdapter
     */
    private $cachePool;


    public function __construct(
        ContainerInterface $container
    )
    {
        $this->clientHandler = null;
        $this->container = $container;
        $this->urls = new ReleaseUrlApi();
        $this->cachePool = new FilesystemAdapter('qownnotes-api', 60, '/tmp/cache/qownnotes-api');
    }

    /**
     * Replace the guzzle client handler for testing.
     * @param object|null $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        return new Client($client_options);
    }

    private function getReleaseClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($this->cachePool),
                self::RELEASE_CACHE_TTL
            )
        );

        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        return new Client($client_options);
    }

    /**
     * @param string $name
     * @return ArrayCollection|LatestRelease[]
     * @throws UnprocessableEntityHttpException
     */
    public function fetchLatestReleases(): ArrayCollection
    {
        /** @var ArrayCollection<int,LatestRelease> $collection */
        $collection = new ArrayCollection();

        $releaseJsonData = $this->fetchLatestReleaseJsonData();

        if (!isset($releaseJsonData[0])) {
            throw new NotFoundHttpException("No release was found!");
        }

        $latestReleaseData = $releaseJsonData[0];
        $tagName = $latestReleaseData["tag_name"];
        $version = $str = substr($tagName, 1);;
        $assets = $latestReleaseData["assets"];

        $nameHash = [
            "QOwnNotes-x86_64.AppImage" => "linux",
            "QOwnNotes.zip" => "windows",
            "QOwnNotes.dmg" => "macos",
        ];

        foreach ($assets as $asset) {
            $name = $asset["name"];

            if (!isset($nameHash[$name])) {
                continue;
            }

            $id = $nameHash[$name];

            // TODO: Get releaseChangesHtml
            $releaseChangesHtml = "Todo";

            $lastRelease = new LatestRelease();
            $lastRelease->setIdentifier($id);
            $lastRelease->setUrl($asset["browser_download_url"]);
            $lastRelease->setVersion($version);
            $lastRelease->setDateCreated(new \DateTime($asset["created_at"]));
            $lastRelease->setReleaseChangesHtml($releaseChangesHtml);
            $collection->add($lastRelease);
        }

        return $collection;
    }

    /**
     * @param string $id
     * @return LatestRelease
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     */
    public function fetchLatestRelease(string $id): LatestRelease {
        $latestReleases = $this->fetchLatestReleases();

        foreach($latestReleases as $latestRelease) {
            if ($latestRelease->getIdentifier() === $id) {
                return $latestRelease;
            }
        }

        throw new NotFoundHttpException('Latest release was not found!');
    }

    public function fetchLatestReleaseJsonData(): array {
        $client = $this->getReleaseClient();

        try {
            // e.g. https://campusqr-dev.tugraz.at/location/list
            $url = $this->urls->getReleasesRequestUrl("pbek", "QOwnNotes");

            $options = [
                'headers' => [ 'Accept' => 'application/vnd.github.v3+json' ]
            ];

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (GuzzleException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Latest release could not be loaded: %s',
                $e->getMessage()));
        } catch (\Exception|UriException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Latest release could not be loaded: %s',
                $e->getMessage()));
        }
    }

    /**
     * @param $jsonData
     * @return LatestRelease
     */
    public function latestReleaseFromJsonItem($identifier, $jsonData): LatestRelease {
        $latestRelease = new LatestRelease();
        $latestRelease->setIdentifier($jsonData["id"]);
        $latestRelease->setUrl($jsonData["name"]);

        return $latestRelease;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     *
     * @throws UnprocessableEntityHttpException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return self::decodeJSON((string) $body, true);
        } catch (JsonException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid json: %s', $e->getMessage()));
        }
    }

    /**
     * Like json_decode but throws on invalid json data.
     *
     * @throws JsonException
     *
     * @return mixed
     */
    public static function decodeJSON(string $json, bool $assoc = false)
    {
        $result = json_decode($json, $assoc);
        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            throw new JsonException(sprintf('%s: "%s"', json_last_error_msg(), print_r($json, true)));
        }

        return $result;
    }
}
