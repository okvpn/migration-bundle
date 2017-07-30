<?php

namespace Okvpn\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Okvpn\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Okvpn\Bundle\MigrationBundle\Event\MigrationEvents;

/**
 * Executes ORM data fixtures.
 *
 * @deprecated since 1.2 and will be removed in 1.3. Fixtures package will be moved to another separate repository,
 *             it step needed to reduces the count of not necessary dependencies
 */
class DataFixturesExecutor implements DataFixturesExecutorInterface
{
    /** @var EntityManager */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var callable|null */
    private $logger;

    /**
     * @param EntityManager            $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManager $em, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $fixtures, $fixturesType)
    {
        $event = new MigrationDataFixturesEvent($this->em, $fixturesType, $this->logger);
        $this->eventDispatcher->dispatch(MigrationEvents::DATA_FIXTURES_PRE_LOAD, $event);

        $executor = new ORMExecutor($this->em);
        if (null !== $this->logger) {
            $executor->setLogger($this->logger);
        }
        $executor->execute($fixtures, true);

        $this->eventDispatcher->dispatch(MigrationEvents::DATA_FIXTURES_POST_LOAD, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
