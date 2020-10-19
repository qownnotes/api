<?php
/**
 * AppRelease entity class
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_release")
 */
class AppRelease
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $version;

    /**
     * @ORM\Column(type="text")
     */
    protected $releaseChangesMarkdown;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

//    /**
//     * @ORM\Column(type="integer")
//     */
//    protected $created;

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
     * Set version
     *
     * @param string $version
     * @return AppRelease
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set releaseChangesMarkdown
     *
     * @param string $releaseChangesMarkdown
     * @return AppRelease
     */
    public function setReleaseChangesMarkdown($releaseChangesMarkdown)
    {
        $this->releaseChangesMarkdown = $releaseChangesMarkdown;

        return $this;
    }

    /**
     * Get releaseChangesMarkdown
     *
     * @return string
     */
    public function getReleaseChangesMarkdown()
    {
        return $this->releaseChangesMarkdown;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppRelease
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

//    /**
//     * Set created
//     *
//     * @param \integer $created
//     * @return AppRelease
//     */
//    public function setCreated($created)
//    {
//        $this->created = $created;
//
//        return $this;
//    }
//
//    /**
//     * Get created
//     *
//     * @return \integer
//     */
//    public function getCreated()
//    {
//        return $this->created;
//    }
}
