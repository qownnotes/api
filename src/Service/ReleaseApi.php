<?php

declare(strict_types=1);
/**
 * Release API service.
 */

namespace App\Service;

use App\Entity\AppRelease;
use App\Entity\LatestRelease;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Michelf\Markdown;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ReleaseApi
{
    public const RELEASE_CACHE_TTL = 60;
    public const REQUEST_METHOD_GET = 'GET';

    private $clientHandler;

    /**
     * @var ReleaseUrlApi
     */
    private $urls;

    private CacheItemPoolInterface $cachePool;

    private CacheInterface $cache;

    private int $cacheTTL;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        CacheItemPoolInterface $cachePool,
    ) {
        if (!$cachePool instanceof CacheInterface) {
            throw new \LogicException('The application cache must implement Symfony CacheInterface.');
        }

        $this->em = $em;
        $this->clientHandler = null;
        $this->urls = new ReleaseUrlApi();
        $this->cachePool = $cachePool;
        $this->cache = $cachePool;
        $this->cacheTTL = self::RELEASE_CACHE_TTL;
    }

    /**
     * Replace the guzzle client handler for testing.
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        $client_options = [
            'handler' => HandlerStack::create($this->clientHandler),
        ];

        return new Client($client_options);
    }

    /**
     * @return ArrayCollection|LatestRelease[]
     *
     * @throws \Exception
     */
    public function fetchLatestReleases(array $filters = []): ArrayCollection
    {
        try {
            $latestReleases = $this->fetchLatestReleasesFromGitHub($filters);
        } catch (UnprocessableEntityHttpException $e) {
            $storedReleases = $this->fetchLatestReleasesFromDatabase($filters);
            if (!$storedReleases->isEmpty()) {
                return $storedReleases;
            }

            throw $e;
        }

        if ($latestReleases->isEmpty()) {
            $storedReleases = $this->fetchLatestReleasesFromDatabase($filters);
            if (!$storedReleases->isEmpty()) {
                return $storedReleases;
            }
        }

        return $latestReleases;
    }

    private function fetchLatestReleasesFromGitHub(array $filters): ArrayCollection
    {
        /** @var ArrayCollection<int,LatestRelease> $collection */
        $collection = new ArrayCollection();

        $latestReleaseData = $this->fetchLatestReleaseJsonData();

        if (!isset($latestReleaseData) || 0 === count($latestReleaseData)) {
            throw new NotFoundHttpException('No release was found!');
        }

        $tagName = $latestReleaseData['tag_name'];
        $latestVersion = $str = substr($tagName, 1);
        $assets = $latestReleaseData['assets'];

        $nameHash = [
            'QOwnNotes-x86_64.AppImage' => 'linux',
            'QOwnNotes.zip' => 'windows',
            'QOwnNotes.dmg' => 'macos',
        ];

        $version = $filters['version'] ?? '';
        $needUpdate = version_compare($version, $latestVersion, '<');

        if ('' !== $version && $needUpdate) {
            $releaseChangesMarkdown = $this->getChangeLogChangesFromGitHubSinceVersion($tagName, $version);
        } else {
            $releaseChangesMarkdown = $this->getReleaseChangesFromBody($latestReleaseData, $latestVersion);
            if ('' === $releaseChangesMarkdown) {
                $releaseChangesMarkdown = $this->getChangeLogChangesFromGitHubForVersion($tagName, $latestVersion);
            }
        }

        $releaseChangesHtml = Markdown::defaultTransform($releaseChangesMarkdown);

        foreach ($assets as $asset) {
            $name = $asset['name'];

            if (!isset($nameHash[$name])) {
                continue;
            }

            $id = $nameHash[$name];

            $lastRelease = new LatestRelease();
            $lastRelease->setIdentifier($id);
            $lastRelease->setUrl($asset['browser_download_url']);
            $lastRelease->setVersion($latestVersion);
            $lastRelease->setDateCreated(new \DateTime($asset['created_at']));
            $lastRelease->setReleaseChangesMarkdown($releaseChangesMarkdown);
            $lastRelease->setReleaseChangesHtml($releaseChangesHtml);
            $lastRelease->setNeedUpdate($needUpdate);
            $collection->add($lastRelease);
        }

        return $collection;
    }

    private function getReleaseChangesFromBody(array $latestReleaseData, string $latestVersion): string
    {
        $body = trim((string) ($latestReleaseData['body'] ?? ''));
        if ('' === $body) {
            return '';
        }

        $matches = [];
        preg_match(
            '/(?:\A|\R)##\s+'.preg_quote($latestVersion, '/').'\s*\R+(.*?)(?=\R##\s+|\z)/su',
            $body,
            $matches,
        );
        $releaseChanges = trim($matches[1] ?? '');

        return '' === trim(strip_tags(Markdown::defaultTransform($releaseChanges))) ? '' : $releaseChanges;
    }

    private function fetchLatestReleasesFromDatabase(array $filters): ArrayCollection
    {
        /** @var AppRelease[] $appReleases */
        $appReleases = $this->em->getRepository(AppRelease::class)
            ->findBy([], ['dateCreated' => 'DESC'], 100);

        /** @var ArrayCollection<int,LatestRelease> $collection */
        $collection = new ArrayCollection();
        if ([] === $appReleases) {
            return $collection;
        }

        $latestRelease = $appReleases[0];
        $latestVersion = $latestRelease->getVersion();
        $version = $filters['version'] ?? '';
        $needUpdate = version_compare($version, $latestVersion, '<');
        $releaseChangesMarkdown = $latestRelease->getReleaseChangesMarkdown();

        if ('' !== $version && $needUpdate) {
            $changes = [];
            foreach ($appReleases as $appRelease) {
                if (!version_compare($appRelease->getVersion(), $version, '>')) {
                    break;
                }

                $changes[] = sprintf(
                    "## %s\n\n%s",
                    $appRelease->getVersion(),
                    $appRelease->getReleaseChangesMarkdown(),
                );
            }
            $releaseChangesMarkdown = implode("\n\n", $changes);
        }

        $releaseChangesHtml = Markdown::defaultTransform($releaseChangesMarkdown);
        $assets = [
            'linux' => 'QOwnNotes-x86_64.AppImage',
            'windows' => 'QOwnNotes.zip',
            'macos' => 'QOwnNotes.dmg',
        ];

        foreach ($assets as $identifier => $assetName) {
            $release = new LatestRelease();
            $release->setIdentifier($identifier);
            $release->setUrl(sprintf(
                'https://github.com/pbek/QOwnNotes/releases/download/v%s/%s',
                rawurlencode($latestVersion),
                rawurlencode($assetName),
            ));
            $release->setVersion($latestVersion);
            $release->setDateCreated($latestRelease->getDateCreated());
            $release->setReleaseChangesMarkdown($releaseChangesMarkdown);
            $release->setReleaseChangesHtml($releaseChangesHtml);
            $release->setNeedUpdate($needUpdate);
            $collection->add($release);
        }

        return $collection;
    }

    /**
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function fetchLatestRelease(string $id, array $filters = []): LatestRelease
    {
        $latestReleases = $this->fetchLatestReleases($filters);

        // also allow "macosx" for Qt compatibility
        if ('macosx' == $id) {
            $id = 'macos';
        }

        foreach ($latestReleases as $latestRelease) {
            if ($latestRelease->getIdentifier() === $id) {
                $this->sendLatestReleaseMatomoEvent($latestRelease, $filters);

                return $latestRelease;
            }
        }

        throw new NotFoundHttpException('Latest release was not found!');
    }

    private function sendLatestReleaseMatomoEvent(LatestRelease $latestRelease, array $filters)
    {
        $release = $filters['release'] ?? '';
        $debug = $filters['debug'] ?? 1;
        $os = $filters['os'] ?? '';
        $cid = $filters['cid'] ?? '';
        $version = $filters['version'] ?? '';
        $updateMode = $filters['um'] ?? '';
        $ipAddress = $this->getIPAddress();
        $anonymousString = '';

        if ('' === $cid) {
            $anonymousString = ', anon';
            $cid = trim("$release $os $ipAddress");
        }

        if ('' === trim($cid)) {
            $cid = mt_rand();
        }

        $debugString = 1 == $debug ? 'Debug' : '';
        $eventLabel = trim("$version $os $release [m$updateMode$anonymousString] $debugString");

        // send a request to the Matomo server
        $this->sendMatomoEvent(
            $cid,
            $ipAddress,
            $version,
            $latestRelease->getIdentifier(),
            $os,
            $release,
            $debug,
            $updateMode,
            'web',
            'update request',
            $eventLabel
        );
    }

    public function fetchLatestReleaseJsonData(): array
    {
        try {
            $url = $this->urls->getReleasesRequestUrl('pbek', 'QOwnNotes');

            $options = [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ];

            $user = $this->getEnv('GITHUB_USER');
            $token = $this->getEnv('GITHUB_ACCESS_TOKEN');
            if ('' !== $user && '' !== $token) {
                $options['auth'] = [$user, $token];
            }

            return $this->fetchCachedGitHubValue(
                $url,
                $options,
                fn (ResponseInterface $response): array => $this->decodeResponse($response),
            );
        } catch (GuzzleException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Latest release could not be loaded: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException(sprintf('Latest release could not be loaded: %s', $e->getMessage()));
        }
    }

    public function latestReleaseFromJsonItem($identifier, $jsonData): LatestRelease
    {
        $latestRelease = new LatestRelease();
        $latestRelease->setIdentifier($jsonData['id']);
        $latestRelease->setUrl($jsonData['name']);

        return $latestRelease;
    }

    /**
     * @throws UnprocessableEntityHttpException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return self::decodeJSON((string) $body, true);
        } catch (\JsonException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid json: %s', $e->getMessage()));
        }
    }

    /**
     * Like json_decode but throws on invalid json data.
     *
     * @throws \JsonException
     */
    public static function decodeJSON(string $json, bool $assoc = false)
    {
        $result = json_decode($json, $assoc);
        $json_error = json_last_error();
        if (JSON_ERROR_NONE !== $json_error) {
            throw new \JsonException(sprintf('%s: "%s"', json_last_error_msg(), print_r($json, true)));
        }

        return $result;
    }

    /**
     * Parses the change log file CHANGELOG.md in a repository on GitHub at a certain tag
     * and returns the text for a certain version string.
     *
     * @return string the changes text
     */
    private function getChangeLogChangesFromGitHubForVersion(string $tag, string $versionString)
    {
        // load the change log file
        $changeLogData = $this->fetchChangeLog($tag);

        $matches = [];
        // parse the changelog
        preg_match('/## '.$versionString.'\n(.+?)\n\n## [\d.]+/sim', $changeLogData, $matches);

        return isset($matches[1]) ? trim($matches[1]) : '';
    }

    /**
     * Fetches a file in a repository on GitHub from a certain branch / tag.
     *
     * @return string the changes text
     */
    private function fetchRawFileFromGitHub(string $identifier, string $fileName)
    {
        $url = "https://raw.githubusercontent.com/pbek/QOwnNotes/$identifier/$fileName";

        // load the file
        return file_get_contents($url);
    }

    /**
     * Parses the change log file CHANGELOG.md in a repository on GitHub at a certain tag
     * and returns the text above the version string.
     *
     * @return string the changes text
     */
    private function getChangeLogChangesFromGitHubSinceVersion(string $tag, string $versionString)
    {
        $changeLogData = $this->fetchChangeLog($tag);

        // get the text above the version string
        $dataList = explode("## $versionString\n", $changeLogData);

        return trim($dataList[0]);
    }

    private function fetchChangeLog($tag): string
    {
        try {
            $url = $this->urls->getChangeLogUrl($tag);

            return $this->fetchCachedGitHubValue(
                $url,
                [],
                static fn (ResponseInterface $response): string => (string) $response->getBody(),
            );
        } catch (RequestException $e) {
            if ('release' !== $tag && 404 === $e->getResponse()?->getStatusCode()) {
                // The changelog tag can briefly lag behind the release during publishing.
                return $this->fetchChangeLog('release');
            }

            throw new UnprocessableEntityHttpException(sprintf('Changelog could not be loaded: %s', $e->getMessage()));
        } catch (\Throwable $e) {
            throw new UnprocessableEntityHttpException(sprintf('Changelog could not be loaded: %s', $e->getMessage()));
        }
    }

    /**
     * Cache fresh GitHub responses briefly while retaining the last successful value.
     * Symfony's cache callback also locks concurrent refreshes for the same URL.
     */
    private function fetchCachedGitHubValue(string $url, array $options, callable $transform): mixed
    {
        $keyHash = hash('sha256', $url);
        $freshKey = 'github_response_'.$keyHash;
        $fallbackKey = 'github_fallback_'.$keyHash;

        return $this->cache->get($freshKey, function (ItemInterface $item) use ($url, $options, $transform, $fallbackKey) {
            $item->expiresAfter($this->cacheTTL);

            try {
                $response = $this->getClient()->request(self::REQUEST_METHOD_GET, $url, $options);
                $value = $transform($response);

                $fallbackItem = $this->cachePool->getItem($fallbackKey);
                $fallbackItem->set($value);
                $this->cachePool->save($fallbackItem);

                return $value;
            } catch (\Throwable $e) {
                $fallbackItem = $this->cachePool->getItem($fallbackKey);
                if ($fallbackItem->isHit()) {
                    return $fallbackItem->get();
                }

                throw $e;
            }
        });
    }

    /**
     * @param string $ipOverride
     * @param string $versionString
     * @param string $id
     * @param string $os
     * @param string $release
     * @param int    $debug
     * @param int    $updateMode
     * @param string $category
     * @param string $action
     * @param string $label
     * @param int    $value
     */
    private function sendMatomoEvent($userId, $ipOverride = '', $versionString = '', $id = '', $os = '', $release = '', $debug = 0, $updateMode = 0, $category = '', $action = '', $label = '', $value = 0)
    {
        $updateModeText = 'Unknown';
        switch ($updateMode) {
            case 1:
                $updateModeText = 'AppStart';
                break;
            case 2:
                $updateModeText = 'Manual';
                break;
            case 3:
                $updateModeText = 'Periodic';
                break;
        }

        $updateModeText .= " ($updateMode)";
        $idSite = (1 == $debug) ? 6 : 5;

        $matomoTracker = new \MatomoTracker($idSite, $this->getEnv('MATOMO_URL', 'https://p.qownnotes.org'));
        $matomoTracker->setRequestTimeout(5);
        $matomoTracker->setIp($ipOverride);
        $matomoTracker->setTokenAuth($this->getEnv('MATOMO_AUTH_TOKEN'));

        try {
            $matomoTracker->setCustomTrackingParameter('dimension1', $versionString);
        } catch (\Exception $e) {
        }

        try {
            $matomoTracker->setCustomTrackingParameter('dimension3', (string) $debug);
        } catch (\Exception $e) {
        }

        try {
            $matomoTracker->setCustomTrackingParameter('dimension7', $os);
        } catch (\Exception $e) {
        }

        try {
            $matomoTracker->setCustomTrackingParameter('dimension9', $release);
        } catch (\Exception $e) {
        }

        try {
            $matomoTracker->setCustomTrackingParameter('dimension11', $updateModeText);
        } catch (\Exception $e) {
        }

        // Matomo workaround for macOS
        if ('macos' == $id) {
            $os = "Macintosh $os";
        }

        $matomoTracker->setUserAgent("Mozilla/5.0 ($os) MatomoTracker/1.0 (PHP)");

        try {
            // we want to try to set the _id hash
            $matomoTracker->setVisitorId((string) $userId);
        } catch (\Exception $e) {
            try {
                $matomoTracker->setUserId((string) $userId);
            } catch (\Exception $e) {
            }
        }

        return $matomoTracker->doTrackEvent($category, $action, $label, $value);
    }

    /**
     * Returns the IP address of the user.
     *
     * @return string
     */
    private function getIPAddress()
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // for proxy servers like CloudFlare
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ipAddress;
    }

    /**
     * Stores an app release if it does not exist.
     *
     * @return AppRelease|false
     */
    public function storeAppReleaseIfNotExists(
        string $versionString,
        string $changeLogText,
        \DateTime $publishedAt,
    ) {
        $appRelease = $this->em->getRepository(AppRelease::class)
            ->findOneBy(['version' => $versionString]);

        if (null === $appRelease) {
            $appRelease = new AppRelease();
            $appRelease->setVersion($versionString);
            $appRelease->setReleaseChangesMarkdown($changeLogText);
            $appRelease->setDateCreated($publishedAt);

            // persist data
            $this->em->persist($appRelease);
            $this->em->flush();

            return $appRelease;
        }

        return false;
    }

    /**
     * Return environment variables or variables set in the .env.
     *
     * @param string $default
     *
     * @return string
     */
    public function getEnv(string $varName, $default = '')
    {
        $value = getenv($varName);

        return false === $value ? ($_ENV[$varName] ?? $default) : $value;
    }
}
