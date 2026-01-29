<?php

/**
 * AppRelease entity class.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'app_release')]
class AppRelease
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    protected string $version;

    #[ORM\Column(type: 'text')]
    protected string $releaseChangesMarkdown;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $dateCreated;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set version.
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set releaseChangesMarkdown.
     */
    public function setReleaseChangesMarkdown(string $releaseChangesMarkdown): self
    {
        $this->releaseChangesMarkdown = $releaseChangesMarkdown;

        return $this;
    }

    /**
     * Get releaseChangesMarkdown.
     */
    public function getReleaseChangesMarkdown(): string
    {
        return $this->releaseChangesMarkdown;
    }

    /**
     * Set dateCreated.
     */
    public function setDateCreated(\DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated.
     */
    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }
}
