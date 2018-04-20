<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Command\RmCommand;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RmCommandTest extends AbstractCommandTest
{
    /**
     * Test that running the command deletes a file from storage.
     *
     * @test
     */
    public function testDelete()
    {
        $storage = $this->getStorageMock();
        $storage
            ->expects($this->once())
            ->method("delete")
            ->with(
                $this->equalTo("test"),
                $this->equalTo("backup-file.sql.gz")
            );

        $tester = $this->getCommandTester($storage);
        $tester->execute([
            "type" => "database",
            "project" => "test",
            "file"    => "backup-file.sql.gz",
        ]);
    }

    /**
     * Create and configure a tester for the "rm" command.
     *
     * @param StorageInterface $storage
     *
     * @return CommandTester
     */
    protected function getCommandTester($storage)
    {
        $command = new RmCommand($this->getConfigMock(), $this->getStorageFactoryMock($storage));

        return new CommandTester($command);
    }
}
