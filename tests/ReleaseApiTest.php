<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\AppRelease;
use App\Service\ReleaseApi;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ReleaseApiTest extends TestCase
{
    public function testReturnsLastSuccessfulReleaseWhenGitHubIsRateLimited(): void
    {
        $releaseUrl = 'https://api.github.com/repos/pbek/QOwnNotes/releases/latest';
        $changeLogUrl = 'https://raw.githubusercontent.com/pbek/QOwnNotes/v26.8.4/CHANGELOG.md';
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson()),
            new Response(200, [], "## 26.8.4\n\nChanges\n\n## 26.8.3\n\nOlder"),
            new Response(429, [], 'Too Many Requests'),
            new Response(429, [], 'Too Many Requests'),
        ]);
        $cache = new ArrayAdapter();
        $api = $this->createReleaseApi($handler, $cache);

        self::assertCount(3, $api->fetchLatestReleases());

        $cache->deleteItem('github_response_'.hash('sha256', $releaseUrl));
        $cache->deleteItem('github_response_'.hash('sha256', $changeLogUrl));

        $releases = $api->fetchLatestReleases();

        self::assertCount(3, $releases);
        self::assertSame('26.8.4', $releases->first()->getVersion());
        self::assertSame('Changes', $releases->first()->getReleaseChangesMarkdown());
    }

    public function testUsesTaggedChangelogWhenReleaseBodyIsNotUseful(): void
    {
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson()),
            new Response(200, [], "## 26.8.4\n\nFallback changes\n\n## 26.8.3\n\nOlder"),
        ]);
        $api = $this->createReleaseApi($handler, new ArrayAdapter());

        $releases = $api->fetchLatestReleases();

        self::assertSame('Fallback changes', $releases->first()->getReleaseChangesMarkdown());
        self::assertCount(0, $handler);
    }

    public function testUsesReleaseBodyWithoutFetchingRawChangelog(): void
    {
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson(
                "## 26.8.4\n\nBody changes\n\n## Released files\n\n- QOwnNotes.zip",
            )),
        ]);
        $api = $this->createReleaseApi($handler, new ArrayAdapter());

        $releases = $api->fetchLatestReleases();

        self::assertSame('Body changes', $releases->first()->getReleaseChangesMarkdown());
        self::assertCount(0, $handler);
    }

    public function testUsesCumulativeChangelogForOlderClientDespiteReleaseBody(): void
    {
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson(
                "## 26.8.4\n\nLatest body changes\n\n## Released files\n\n- QOwnNotes.zip",
            )),
            new Response(200, [], "## 26.8.4\n\nLatest changes\n\n## 26.8.3\n\nOlder changes\n\n## 26.8.2\n\nPrevious changes"),
        ]);
        $api = $this->createReleaseApi($handler, new ArrayAdapter());

        $releases = $api->fetchLatestReleases(['version' => '26.8.2']);

        self::assertSame(
            "## 26.8.4\n\nLatest changes\n\n## 26.8.3\n\nOlder changes",
            $releases->first()->getReleaseChangesMarkdown(),
        );
        self::assertSame(
            'https://raw.githubusercontent.com/pbek/QOwnNotes/v26.8.4/CHANGELOG.md',
            (string) $handler->getLastRequest()->getUri(),
        );
        self::assertCount(0, $handler);
    }

    public function testFallsBackToReleaseBranchWithoutReturningUnreleasedChanges(): void
    {
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson()),
            new Response(404, [], 'Not Found'),
            new Response(200, [], "## 26.8.4\n\nLatest changes\n\n## 26.8.3\n\nOlder changes\n\n## 26.8.2\n\nPrevious changes"),
        ]);
        $api = $this->createReleaseApi($handler, new ArrayAdapter());

        $releases = $api->fetchLatestReleases(['version' => '26.8.2']);

        self::assertSame(
            "## 26.8.4\n\nLatest changes\n\n## 26.8.3\n\nOlder changes",
            $releases->first()->getReleaseChangesMarkdown(),
        );
        self::assertSame(
            'https://raw.githubusercontent.com/pbek/QOwnNotes/release/CHANGELOG.md',
            (string) $handler->getLastRequest()->getUri(),
        );
        self::assertCount(0, $handler);
    }

    public function testReturnsStoredReleaseWhenGitHubIsUnavailableAndCacheIsEmpty(): void
    {
        $handler = new MockHandler([
            new Response(429, [], 'Too Many Requests'),
        ]);
        $storedRelease = (new AppRelease())
            ->setVersion('26.8.4')
            ->setReleaseChangesMarkdown('Stored changes')
            ->setDateCreated(new \DateTime('2026-08-17T12:00:00Z'));
        $api = $this->createReleaseApi($handler, new ArrayAdapter(), [$storedRelease]);

        $releases = $api->fetchLatestReleases();

        self::assertCount(3, $releases);
        self::assertSame('26.8.4', $releases->first()->getVersion());
        self::assertSame('Stored changes', $releases->first()->getReleaseChangesMarkdown());
        self::assertSame(
            'https://github.com/pbek/QOwnNotes/releases/download/v26.8.4/QOwnNotes-x86_64.AppImage',
            $releases->first()->getUrl(),
        );
    }

    /**
     * @param AppRelease[] $storedReleases
     */
    private function createReleaseApi(MockHandler $handler, ArrayAdapter $cache, array $storedReleases = []): ReleaseApi
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn($storedReleases);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(AppRelease::class)->willReturn($repository);
        $api = new ReleaseApi(
            $entityManager,
            $cache,
        );
        $api->setClientHandler($handler);

        return $api;
    }

    private function releaseJson(?string $body = null): string
    {
        return json_encode([
            'tag_name' => 'v26.8.4',
            'body' => $body,
            'assets' => [
                ['name' => 'QOwnNotes-x86_64.AppImage', 'browser_download_url' => 'https://example.com/linux', 'created_at' => '2026-08-17T12:00:00Z'],
                ['name' => 'QOwnNotes.zip', 'browser_download_url' => 'https://example.com/windows', 'created_at' => '2026-08-17T12:00:00Z'],
                ['name' => 'QOwnNotes.dmg', 'browser_download_url' => 'https://example.com/macos', 'created_at' => '2026-08-17T12:00:00Z'],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
