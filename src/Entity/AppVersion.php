<?php
/**
 * AppVersion entity class
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="project_version")
 */
class AppVersion
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $repository;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $versionString;

    /**
     * @ORM\Column(type="text")
     */
    protected $changeLogText;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $publishedAt;

    /**
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set repository
     *
     * @param string $repository
     * @return AppVersion
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get repository
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set versionString
     *
     * @param string $versionString
     * @return AppVersion
     */
    public function setVersionString($versionString)
    {
        $this->versionString = $versionString;

        return $this;
    }

    /**
     * Get versionString
     *
     * @return string
     */
    public function getVersionString()
    {
        return $this->versionString;
    }

    /**
     * Set changeLogText
     *
     * @param string $changeLogText
     * @return AppVersion
     */
    public function setChangeLogText($changeLogText)
    {
        $this->changeLogText = $changeLogText;

        return $this;
    }

    /**
     * Get changeLogText
     *
     * @return string
     */
    public function getChangeLogText()
    {
        return $this->changeLogText;
    }

    /**
     * Set publishedAt
     *
     * @param \DateTime $publishedAt
     * @return AppVersion
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Set created
     *
     * @param \integer $created
     * @return AppVersion
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \integer
     */
    public function getCreated()
    {
        return $this->created;
    }
}
