<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *   collectionOperations={"get"},
 *   itemOperations={
 *     "get"={"openapi_context"={
 *       "parameters"={
 *         {"name"="identifier", "in"="path", "description"="Type of release [linux, windows, macos]", "type"="string", "enum"={"linux", "windows", "macos"}, "example"="linux"},
 *         {"name"="version", "in"="query", "description"="Version of the application", "type"="string", "example"="21.8.2"},
 *         {"name"="debug", "in"="query", "description"="Debugging release? [0, 1]", "type"="number", "enum"={"0", "1"}, "example"="1"},
 *         {"name"="cid", "in"="query", "description"="Client id", "type"="number"},
 *         {"name"="os", "in"="query", "description"="Operating system", "type"="string"},
 *         {"name"="release", "in"="query", "description"="Release type", "type"="string"},
 *         {"name"="um", "in"="query", "description"="Update mode", "type"="number"},
 *     }}},
 *   },
 *   iri="http://www.qownnotes.org/Release",
 *   description="Latest release of QOwnNotes",
 *   normalizationContext={"groups"={"LatestRelease:output"}}
 * )
 */
class LatestRelease
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LatestRelease:output"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"linux", "windows", "macos"},
     *             "example"="linux"
     *         }
     *     }
     * )
     *
     * @var string
     */
    private $identifier;

    /**
     * @var string
     * @ApiProperty(iri="https://schema.org/url")
     * @Groups({"LatestRelease:output"})
     */
    private $url;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/version")
     * @Groups({"LatestRelease:output"})
     */
    private $version;

    /**
     * @var \DateTime
     * @ApiProperty(iri="http://schema.org/dateCreated")
     * @Groups({"LatestRelease:output"})
     */
    private $dateCreated;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/text")
     * @Groups({"LatestRelease:output"})
     */
    private $releaseChangesMarkdown;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/text")
     * @Groups({"LatestRelease:output"})
     */
    private $releaseChangesHtml;

    /**
     * @var bool
     * @ApiProperty(iri="http://schema.org/Boolean")
     * @Groups({"LatestRelease:output"})
     */
    private $needUpdate;


    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getReleaseChangesHtml(): ?string
    {
        return $this->releaseChangesHtml;
    }

    public function setReleaseChangesHtml(string $releaseChangesHtml): self
    {
        $this->releaseChangesHtml = $releaseChangesHtml;

        return $this;
    }

    public function getReleaseChangesMarkdown(): ?string
    {
        return $this->releaseChangesMarkdown;
    }

    public function setReleaseChangesMarkdown(string $releaseChangesMarkdown): self
    {
        $this->releaseChangesMarkdown = $releaseChangesMarkdown;

        return $this;
    }

    public function getNeedUpdate(): ?bool
    {
        return $this->needUpdate;
    }

    public function setNeedUpdate(bool $needUpdate): self
    {
        $this->needUpdate = $needUpdate;

        return $this;
    }
}
