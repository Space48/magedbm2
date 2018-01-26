<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Command\PutCommand;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use Meanbee\Magedbm2\Service\TableExpander\Magento;
use Meanbee\Magedbm2\Service\TableExpanderInterface;
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

        $tester = $this->getCommandTester(null, $database, $storage);
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
            ->with(
                $this->equalTo("test"),
                $this->equalTo("test_table other_table")
            );

        $tester = $this->getCommandTester(null, $database, null, null, new Magento());
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
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method("clean")
            ->with(
                $this->equalTo("test"),
                $this->equalTo(5)
            );

        $tester = $this->getCommandTester(null, null, $storage);
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
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->never())
            ->method("clean");

        $tester = $this->getCommandTester(null, null, $storage);
        $tester->execute([
            "project"    => "test",
            "--no-clean" => true,
        ]);
    }
    
    /**
     * Create and configure a tester for the "put" command.
     *
     * @param ConfigInterface        $config
     * @param DatabaseInterface      $database
     * @param StorageInterface       $storage
     * @param FilesystemInterface    $filesystem
     * @param TableExpanderInterface $tableexpander
     * @return CommandTester
     */
    protected function getCommandTester($config = null, $database = null, $storage = null, $filesystem = null, $tableexpander = null)
    {
        $config        = $config ?? $this->createMock(ConfigInterface::class);
        $database      = $database ?? $this->createMock(DatabaseInterface::class);
        $storage       = $storage ?? $this->createMock(StorageInterface::class);
        $filesystem    = $filesystem ?? $this->createMock(FilesystemInterface::class);
        $tableexpander = $tableexpander ?? $this->createMock(TableExpanderInterface::class);
        
        $command = new PutCommand($config, $database, $storage, $filesystem, $tableexpander);

        $tester = new CommandTester($command);

        return $tester;
    }
}
