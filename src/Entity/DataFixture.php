<?php

namespace Okvpn\Bundle\MigrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @deprecated since 1.2 and will be removed in 1.3. Fixtures package will be moved to another separate repository,
 *             it step needed to reduces the count of not necessary dependencies
 *
 * @ORM\Table("okvpn_migrations_data")
 * @ORM\Entity(repositoryClass="Okvpn\Bundle\MigrationBundle\Entity\Repository\DataFixtureRepository")
 */
class DataFixture
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255)
     */
    protected $className;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255, nullable=true)
     */
    protected $version;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loaded_at", type="datetime")
     */
    protected $loadedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoadedAt()
    {
        return $this->loadedAt;
    }

    /**
     * @param \DateTime $loadedAt
     * @return $this
     */
    public function setLoadedAt($loadedAt)
    {
        $this->loadedAt = $loadedAt;

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
