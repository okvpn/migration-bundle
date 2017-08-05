<?php

namespace Okvpn\Bundle\MigrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Okvpn\Bundle\MigrationBundle\Entity\DataFixture;

/**
 * @deprecated since 1.2 and will be removed in 2.0. Fixtures package will be moved to another separate repository,
 *             it step needed to reduces the count of not necessary dependencies
 */
class DataFixtureRepository extends EntityRepository
{
    /**
     * @param $className
     *
     * @return DataFixture[]
     */
    public function findByClassName($className)
    {
        return $this->findBy(['className' => $className]);
    }

    /**
     * @param string $where
     * @param array  $parameters
     *
     * @return bool
     */
    public function isDataFixtureExists($where, array $parameters = [])
    {
        $entityId = $this->createQueryBuilder('m')
            ->select('m.id')
            ->where($where)
            ->setMaxResults(1)
            ->getQuery()
            ->execute($parameters);

        return $entityId ? true : false;
    }

    /**
     * Update data fixture history
     *
     * @param array  $updateFields assoc array with field names and values that should be updated
     * @param string $where        condition
     * @param array  $parameters   optional parameters for where condition
     */
    public function updateDataFixutreHistory(array $updateFields, $where, array $parameters = [])
    {
        $qb = $this->_em
            ->createQueryBuilder()
            ->update('OkvpnMigrationBundle:DataFixture', 'm')
            ->where($where);

        foreach ($updateFields as $fieldName => $fieldValue) {
            $qb->set($fieldName, $fieldValue);
        }
        $qb->getQuery()->execute($parameters);
    }
}
