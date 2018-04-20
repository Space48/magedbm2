<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class FilesystemFactory
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return FilesystemInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function create()
    {
        switch ($this->container->get('filesystem_adapter')) {
            case 'simple':
            default:
                $instance = $this->container->make(\Meanbee\Magedbm2\Service\Filesystem\Simple::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($this->container->get('logger'));
        }

        return $instance;
    }
}
