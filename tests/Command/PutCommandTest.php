<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Command\PutCommand;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PutCommandTest extends TestCase
{

    /**
     * Test that a backup file is uploaded.
     *
     * @test
     */
    public function testUpload()
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects($this->once())
            ->method("dump")
            ->willReturn("/tmp/backup-file.sql.gz");

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method("upload")
            ->with(
                $this->equalTo("test"),
                $this->equalTo("/tmp/backup-file.sql.gz")
            );

        $filesystem = $this->createMock(FilesystemInterface::class);

        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project" => "test",
        ]);
    }

    /**
     * Test that the list of tables to strip gets passed into the database service.
     *
     * @test
     */
    public function testStrip()
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects($this->once())
            ->method("dump")
            ->with($this->equalTo(["test_table", "other_table"]));

        $storage = $this->createMock(StorageInterface::class);
        $filesystem = $this->createMock(FilesystemInterface::class);

        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project" => "test",
            "--strip" => "test_table other_table",
        ]);
    }

    /**
     * Test that the clean up procedure is run after uploading by default.
     *
     * @test
     */
    public function testClean()
    {
        $database = $this->createMock(DatabaseInterface::class);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method("clean")
            ->with(
                $this->equalTo("test"),
                $this->equalTo(5)
            );

        $filesystem = $this->createMock(FilesystemInterface::class);

        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project" => "test",
        ]);
    }

    /**
     * Test that the clean up procedure is skipped if --no-clean is specified.
     *
     * @test
     */
    public function testNoClean()
    {
        $database = $this->createMock(DatabaseInterface::class);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->never())
            ->method("clean");

        $filesystem = $this->createMock(FilesystemInterface::class);


        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project"    => "test",
            "--no-clean" => true,
        ]);
    }

    /**
     * Create and configure a tester for the "put" command.
     *
     * @param DatabaseInterface   $database
     * @param StorageInterface    $storage
     * @param FilesystemInterface $filesystem
     *
     * @return CommandTester
     */
    protected function getCommandTester($database, $storage, $filesystem)
    {
        $command = new PutCommand($database, $storage, $filesystem);

        $tester = new CommandTester($command);

        return $tester;
    }
}
