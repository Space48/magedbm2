<?php

namespace Meanbee\Magedbm2\Service;

use DI\Container;
use Meanbee\Magedbm2\Application\ConfigInterface;

class StorageFactory
{
    /**
     * @var Container
     */
    private $container;
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(Container $container, ConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @return StorageInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function create()
    {
        switch ($this->config->get('selected-storage-adapter')) {
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
