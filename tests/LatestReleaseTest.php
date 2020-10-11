<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\LatestRelease;

class LatestReleaseTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
        // The client implements Symfony HttpClient's `HttpClientInterface`, and the response `ResponseInterface`
        $response = static::createClient()->request('GET', '/latest_releases');

        $this->assertResponseIsSuccessful();
        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/contexts/LatestRelease',
            '@id' => '/latest_releases',
            '@type' => 'hydra:Collection',
        ]);

        // Because test fixtures are automatically loaded between each test, you can assert on them
        $this->assertCount(3, $response->toArray()['hydra:member']);

        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        $this->assertMatchesResourceCollectionJsonSchema(LatestRelease::class);
    }

    public function testGetItem(): void
    {
        $response = static::createClient()->request('GET', '/latest_releases/linux');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/LatestRelease',
            '@type' => 'http://www.qownnotes.org/Release',
            'identifier' => 'linux',
        ]);
        $this->assertMatchesResourceItemJsonSchema(LatestRelease::class);
    }
}