<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class DatabaseFactory
{
    /**
     * @param Container $container
     * @return DatabaseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function create(Container $container)
    {
        switch ($container->get('database_adapter')) {
            case 'shell':
            default:
                $instance = $container->make(\Meanbee\Magedbm2\Service\Database\Shell::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($container->get('logger'));
        }

        return $instance;
    }
}
