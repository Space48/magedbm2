<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class FilesystemFactory
{
    /**
     * @param Container $container
     * @return FilesystemInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function create(Container $container)
    {
        switch ($container->get('filesystem_adapter')) {
            case 'simple':
            default:
                $instance = $container->make(\Meanbee\Magedbm2\Service\Filesystem\Simple::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($container->get('logger'));
        }

        return $instance;
    }
}
