<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

class LatestRelease
{
    #[Groups(['LatestRelease:output'])]
    private string $identifier;

    #[Groups(['LatestRelease:output'])]
    private string $url;

    #[Groups(['LatestRelease:output'])]
    private string $version;

    #[Groups(['LatestRelease:output'])]
    private \DateTime $dateCreated;

    #[Groups(['LatestRelease:output'])]
    private string $releaseChangesMarkdown;

    #[Groups(['LatestRelease:output'])]
    private string $releaseChangesHtml;

    #[Groups(['LatestRelease:output'])]
    private bool $needUpdate;

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
