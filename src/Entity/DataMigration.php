<?php

namespace Okvpn\Bundle\MigrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("okvpn_migrations")
 * @ORM\Entity()
 *
 * @internal
 */
class DataMigration
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="bundle", type="string", length=250)
     */
    public $bundle;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=250)
     */
    public $version;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loaded_at", type="datetime")
     */
    public $loadedAt;
}
