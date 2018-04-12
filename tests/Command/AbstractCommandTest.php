<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractCommandTest extends TestCase
{
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|StorageInterface
     */
    protected function getStorageMock()
    {
        return $this->createMock(StorageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FilesystemInterface
     */
    protected function getFilesystemMock()
    {
        return $this->createMock(FilesystemInterface::class);
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
}
