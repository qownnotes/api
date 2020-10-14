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
use MatomoTracker;
use Michelf\Markdown;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReleaseApi
{
    const RELEASE_CACHE_TTL = 60;

    private $clientHandler;

    /**
     * @var ReleaseUrlApi
     */
    private $urls;

    /**
     * @var FilesystemAdapter
     */
    private $cachePool;


    public function __construct()
    {
        $this->clientHandler = null;
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
     * @param array $filters
     * @return ArrayCollection|LatestRelease[]
     * @throws \Exception
     */
    public function fetchLatestReleases(array $filters = []): ArrayCollection
    {
        /** @var ArrayCollection<int,LatestRelease> $collection */
        $collection = new ArrayCollection();

        $releaseJsonData = $this->fetchLatestReleaseJsonData();

        if (!isset($releaseJsonData[0])) {
            throw new NotFoundHttpException("No release was found!");
        }

        $latestReleaseData = $releaseJsonData[0];
        $tagName = $latestReleaseData["tag_name"];
        $latestVersion = $str = substr($tagName, 1);;
        $assets = $latestReleaseData["assets"];

        $nameHash = [
            "QOwnNotes-x86_64.AppImage" => "linux",
            "QOwnNotes.zip" => "windows",
            "QOwnNotes.dmg" => "macos",
        ];

        $version = $filters["version"] ?? "";
        $needUpdate = version_compare( $version, $latestVersion, "<" );

        $releaseChangesMarkdown = ($version !== "" && $needUpdate) ?
            $this->getChangeLogChangesFromGitHubSinceVersion($tagName, $version) :
            $this->getChangeLogChangesFromGitHubForVersion($tagName, $latestVersion);

        $releaseChangesHtml = Markdown::defaultTransform($releaseChangesMarkdown);

        foreach ($assets as $asset) {
            $name = $asset["name"];

            if (!isset($nameHash[$name])) {
                continue;
            }

            $id = $nameHash[$name];

            $lastRelease = new LatestRelease();
            $lastRelease->setIdentifier($id);
            $lastRelease->setUrl($asset["browser_download_url"]);
            $lastRelease->setVersion($latestVersion);
            $lastRelease->setDateCreated(new \DateTime($asset["created_at"]));
            $lastRelease->setReleaseChangesHtml($releaseChangesHtml);
            $lastRelease->setNeedUpdate($needUpdate);
            $collection->add($lastRelease);
        }

        return $collection;
    }

    /**
     * @param string $id
     * @param array $filters
     * @return LatestRelease
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function fetchLatestRelease(string $id, array $filters): LatestRelease {
        $latestReleases = $this->fetchLatestReleases($filters);

        // also allow "macosx" for Qt compatibility
        if ($id == "macosx") {
            $id = "macos";
        }

        foreach($latestReleases as $latestRelease) {
            if ($latestRelease->getIdentifier() === $id) {
                $this->sendLatestReleaseMatomoEvent($latestRelease, $filters);
                return $latestRelease;
            }
        }

        throw new NotFoundHttpException('Latest release was not found!');
    }

    /**
     * @param LatestRelease $latestRelease
     * @param array $filters
     */
    private function sendLatestReleaseMatomoEvent(LatestRelease $latestRelease, array $filters) {
        $release = $filters["release"] ?? "";
        $debug = $filters["debug"] ?? 1;
        $os = $filters["os"] ?? "";
        $cid = $filters["cid"] ?? "";
        $version = $filters["version"] ?? "";
        $updateMode = $filters["um"] ?? "";
        $ipAddress = $this->getIPAddress();
        $anonymousString = "";

        if ($cid === "") {
            $anonymousString = ", anon";
            $cid = trim("$release $os $ipAddress");
        }

        if (trim($cid) === "") {
            $cid = mt_rand();
        }

        $debugString = $debug == 1 ? "Debug" : "";
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
            "web",
            "update request",
            $eventLabel
        );
    }

    public function fetchLatestReleaseJsonData(): array {
        $client = $this->getReleaseClient();

        try {
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

    /**
     * Parses the change log file CHANGELOG.md in a repository on GitHub at a certain tag
     * and returns the text for a certain version string
     *
     * @param string $tag
     * @param string $versionString
     * @return string the changes text
     */
    private function getChangeLogChangesFromGitHubForVersion(string $tag, string $versionString)
    {
        // load the change log file
        $changeLogData = $this->fetchChangeLog($tag);

        $matches = [];
        // parse the changelog
        preg_match('/## '.$versionString.'\n(.+?)\n\n## [\d.]+/sim', $changeLogData, $matches);

        return isset($matches[1]) ? trim($matches[1]) : "";
    }

    /**
     * Fetches a file in a repository on GitHub from a certain branch / tag
     *
     * @param string $identifier
     * @param string $fileName
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
     * and returns the text above the version string
     *
     * @param string $repository
     * @param string $tag
     * @param string $versionString
     * @return string the changes text
     */
    private function getChangeLogChangesFromGitHubSinceVersion(string $tag, string $versionString)
    {
        $changeLogData = $this->fetchChangeLog($tag);

        // get the text above the version string
        $dataList = explode("## $versionString\n", $changeLogData);

        return trim($dataList[0]);
    }

    private function fetchChangeLog($tag): string {
        $client = $this->getReleaseClient();

        try {
            $url = $this->urls->getChangeLogUrl($tag);

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url);

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Changelog could not be loaded: %s',
                $e->getMessage()));
        } catch (\Exception|UriException $e) {
            throw new UnprocessableEntityHttpException(sprintf('Changelog could not be loaded: %s',
                $e->getMessage()));
        }
    }

    /**
     * @param $userId
     * @param string $ipOverride
     * @param string $versionString
     * @param string $id
     * @param string $os
     * @param string $release
     * @param int $debug
     * @param int $updateMode
     * @param string $category
     * @param string $action
     * @param string $label
     * @param int $value
     * @return mixed
     */
    private function sendMatomoEvent($userId, $ipOverride = "", $versionString = "", $id = "", $os = "", $release = "", $debug = 0, $updateMode = 0, $category = "", $action = "", $label = "", $value = 0)
    {
        $updateModeText = "Unknown";
        switch ($updateMode) {
            case 1:
                $updateModeText = "AppStart";
                break;
            case 2:
                $updateModeText = "Manual";
                break;
            case 3:
                $updateModeText = "Periodic";
                break;
        }

        $updateModeText .= " ($updateMode)";
        $idSite = ($debug == 1) ? 6 : 5;

        $matomoTracker = new MatomoTracker($idSite, "http://p.qownnotes.org");
        $matomoTracker->setIp($ipOverride);
        $matomoTracker->setTokenAuth(getenv("MATOMO_AUTH_TOKEN"));

        try {
            $matomoTracker->setCustomTrackingParameter("dimension1", $versionString);
        } catch (\Exception $e) {}

        try {
            $matomoTracker->setCustomTrackingParameter("dimension3", $debug);
        } catch (\Exception $e) {}

        try {
            $matomoTracker->setCustomTrackingParameter("dimension7", $os);
        } catch (\Exception $e) {}

        try {
            $matomoTracker->setCustomTrackingParameter("dimension9", $release);
        } catch (\Exception $e) {}

        try {
            $matomoTracker->setCustomTrackingParameter("dimension11", $updateModeText);
        } catch (\Exception $e) {}

        // Matomo workaround for macOS
        if ($id == "macos") {
            $os = "Macintosh $os";
        }

        $matomoTracker->setUserAgent("Mozilla/5.0 ($os) MatomoTracker/1.0 (PHP)");

        try {
            // we want to try to set the _id hash
            $matomoTracker->setVisitorId($userId);
        } catch ( \Exception $e ) {
            try {
                $matomoTracker->setUserId($userId);
            } catch (\Exception $e) {}
        }

        return $matomoTracker->doTrackEvent($category, $action, $label, $value);
    }

    /**
     * Returns the IP address of the user
     *
     * @return string
     */
    private function getIPAddress()
    {
        $ipAddress = $_SERVER["REMOTE_ADDR"] ?? "";

        // for proxy servers like CloudFlare
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ipAddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }

        return $ipAddress;
    }
}
