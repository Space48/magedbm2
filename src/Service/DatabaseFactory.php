<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class DatabaseFactory
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
     * @return DatabaseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function create()
    {
        switch ($this->container->get('database_adapter')) {
            case 'shell':
            default:
                $instance = $this->container->make(\Meanbee\Magedbm2\Service\Database\Shell::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($this->container->get('logger'));
        }

        return $instance;
    }
}
