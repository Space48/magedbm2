<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\DatabaseFactory;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemFactory;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageFactory;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractCommandTest extends TestCase
{
    /**
     * @param $instance
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStorageFactoryMock($instance)
    {
        return $this->createFactoryMock(StorageFactory::class, $instance);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|StorageInterface
     */
    protected function getStorageMock()
    {
        return $this->createMock(StorageInterface::class);
    }

    /**
     * @param $instance
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFilesystemFactoryMock($instance)
    {
        return $this->createFactoryMock(FilesystemFactory::class, $instance);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FilesystemInterface
     */
    protected function getFilesystemMock()
    {
        return $this->createMock(FilesystemInterface::class);
    }

    /**
     * @param $instance
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDatabaseFactoryMock($instance)
    {
        return $this->createFactoryMock(DatabaseFactory::class, $instance);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DatabaseInterface
     */
    protected function getDatabaseMock()
    {
        return $this->createMock(DatabaseInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConfigInterface
     */
    protected function getConfigMock()
    {
        return $this->createMock(ConfigInterface::class);
    }

    /**
     * @param $class
     * @param $instance
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFactoryMock($class, $instance)
    {
        $mock = $this->createMock($class);

        if (!is_array($instance)) {
            $instance = [$instance];
        }

        $mock->method('create')
            ->willReturnOnConsecutiveCalls(...$instance);

        return $mock;
    }
}
