<?php

namespace Okvpn\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Okvpn\Bundle\MigrationBundle\Entity\DataFixture;

/**
 * @deprecated since 1.2 and will be removed in 1.3. Fixtures package will be moved to another separate repository,
 *             it step needed to reduces the count of not necessary dependencies
 */
class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @var array
     *  key - class name
     *  value - current loaded version
     */
    protected $dataFixturesClassNames;

    /**
     * Set a list of data fixtures to be updated
     *
     * @param array $classNames
     */
    public function setDataFixtures($classNames)
    {
        $this->dataFixturesClassNames = $classNames;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!empty($this->dataFixturesClassNames)) {
            $loadedAt = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach ($this->dataFixturesClassNames as $className => $version) {
                $dataFixture = null;
                if ($version !== null) {
                    $dataFixture = $manager
                        ->getRepository('OkvpnMigrationBundle:DataFixture')
                        ->findOneBy(['className' => $className]);
                }
                if (!$dataFixture) {
                    $dataFixture = new DataFixture();
                    $dataFixture->setClassName($className);
                }

                $dataFixture
                    ->setVersion($version)
                    ->setLoadedAt($loadedAt);
                $manager->persist($dataFixture);
            }
            $manager->flush();
        }
    }
}
