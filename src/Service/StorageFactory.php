<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class StorageFactory
{
    /**
     * @param Container $container
     * @return StorageInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function create(Container $container)
    {
        switch ($container->get('storage_adapter')) {
            case 's3':
                $instance = $container->make(\Meanbee\Magedbm2\Service\Storage\S3::class);
                break;
            case 'local':
            default:
                $instance = $container->make(\Meanbee\Magedbm2\Service\Storage\Local::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($container->get('logger'));
        }

        return $instance;
    }
}
