<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;

class StorageFactory
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
     * @return StorageInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function create($adapter = null)
    {

        if ($adapter == null) {
            $adapter = $this->container->get('storage_adapter');
        }

        switch ($adapter) {
            case 's3':
                $instance = $this->container->make(\Meanbee\Magedbm2\Service\Storage\S3::class);
                break;
            case 'digitalocean-spaces':
                $instance = $this->container->make(\Meanbee\Magedbm2\Service\Storage\DigitalOceanSpaces::class);
                break;
            case 'local':
            default:
                $instance = $this->container->make(\Meanbee\Magedbm2\Service\Storage\Local::class);
        }

        if ($instance instanceof \Psr\Log\LoggerAwareInterface) {
            $instance->setLogger($this->container->get('logger'));
        }

        return $instance;
    }
}
