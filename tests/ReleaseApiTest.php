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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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

    public function testDoesNotRetryRateLimitedChangelogAgainstMainBranch(): void
    {
        $handler = new MockHandler([
            new Response(200, [], $this->releaseJson()),
            new Response(429, [], 'Too Many Requests'),
            new Response(200, [], 'main branch must not be requested'),
        ]);
        $api = $this->createReleaseApi($handler, new ArrayAdapter());

        try {
            $api->fetchLatestReleases();
            self::fail('A rate-limited changelog must fail when no fallback is cached.');
        } catch (UnprocessableEntityHttpException) {
            self::assertCount(1, $handler);
        }
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

    private function releaseJson(): string
    {
        return json_encode([
            'tag_name' => 'v26.8.4',
            'assets' => [
                ['name' => 'QOwnNotes-x86_64.AppImage', 'browser_download_url' => 'https://example.com/linux', 'created_at' => '2026-08-17T12:00:00Z'],
                ['name' => 'QOwnNotes.zip', 'browser_download_url' => 'https://example.com/windows', 'created_at' => '2026-08-17T12:00:00Z'],
                ['name' => 'QOwnNotes.dmg', 'browser_download_url' => 'https://example.com/macos', 'created_at' => '2026-08-17T12:00:00Z'],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
