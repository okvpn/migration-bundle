<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MigrationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Okvpn\Bundle\MigrationBundle\DependencyInjection\Compiler\MigrationExtensionPass;

final class OkvpnMigrationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MigrationExtensionPass());
    }
}
