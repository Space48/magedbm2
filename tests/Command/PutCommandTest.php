<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Command\PutCommand;
use Meanbee\Magedbm2\Service\Database\Fake;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use Meanbee\Magedbm2\Helper\TableGroupExpander;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use VirtualFileSystem\FileSystem;
use VirtualFileSystem\Structure\File;

class PutCommandTest extends AbstractCommandTest
{
    /**
     * @var FileSystem
     */
    private $vfs;

    /**
     * @var File
     */
    private $file;

    protected function setUp()
    {
        $this->vfs = new FileSystem();
        $this->file = $this->vfs->path(Fake::DUMP_FILE_LOCATION);
    }

    /**
     * Test that a backup file is uploaded.
     *
     * @test
     */
    public function testUpload()
    {
        $storage = $this->getStorageMock();
        $storage
            ->expects($this->once())
            ->method("upload")
            ->with(
                $this->equalTo("test"),
                $this->equalTo($this->file)
            );

        $tester = $this->getCommandTester(null, null, $storage);
        $exitCode = $tester->execute([
            "project" => "test",
        ]);

        if ($exitCode !== 0) {
            $this->fail(sprintf("Exit Code: %s, Output: %s", $exitCode, $tester->getDisplay()));
        }
    }

    /**
     * Test that the list of tables to strip gets passed into the database service.
     *
     * @test
     */
    public function testStrip()
    {
        $database = $this->getMockBuilder(Fake::class)
            ->setConstructorArgs([$this->vfs])
            ->getMock();

        $database
            ->expects($this->once())
            ->method("dump")
            ->with(
                $this->equalTo("test"),
                $this->equalTo("test_table other_table")
            )->willReturn($this->file);

        // Simulate the fact that the file was written.
        file_put_contents($this->file, date('r'));

        $tester = $this->getCommandTester(null, $database, null, null, new TableGroupExpander());
        $exitCode = $tester->execute([
            "project" => "test",
            "--strip" => "test_table other_table",
        ]);

        if ($exitCode !== 0) {
            $this->fail(sprintf("Exit Code: %s, Output: %s, Dump File: %s", $exitCode, $tester->getDisplay(), $this->file));
        }
    }

    /**
     * Test that the clean up procedure is run after uploading by default.
     *
     * @test
     */
    public function testClean()
    {
        $storage = $this->getStorageMock();
        $storage
            ->expects($this->once())
            ->method("clean")
            ->with(
                $this->equalTo("test"),
                $this->equalTo(5)
            );

        $tester = $this->getCommandTester(null, null, $storage);
        $exitCode = $tester->execute([
            "project" => "test",
        ]);

        if ($exitCode !== 0) {
            $this->fail(sprintf("Exit Code: %s, Output: %s", $exitCode, $tester->getDisplay()));
        }
    }

    /**
     * Test that the clean up procedure is skipped if --no-clean is specified.
     *
     * @test
     */
    public function testNoClean()
    {
        $storage = $this->getStorageMock();
        $storage
            ->expects($this->never())
            ->method("clean");

        $tester = $this->getCommandTester(null, null, $storage);
        $exitCode = $tester->execute([
            "project"    => "test",
            "--no-clean" => true,
        ]);

        if ($exitCode !== 0) {
            $this->fail(sprintf("Exit Code: %s, Output: %s", $exitCode, $tester->getDisplay()));
        }
    }
    
    /**
     * Create and configure a tester for the "put" command.
     *
     * @param ConfigInterface        $config
     * @param DatabaseInterface      $database
     * @param StorageInterface       $storage
     * @param FilesystemInterface    $filesystem
     * @param TableGroupExpander     $tableexpander
     * @return CommandTester
     */
    protected function getCommandTester($config = null, $database = null, $storage = null, $filesystem = null, $tableexpander = null)
    {
        $config        = $config ?? $this->getConfigMock();
        $database      = $database ?? new Fake($this->vfs);
        $storage       = $storage ?? $this->getStorageMock();
        $filesystem    = $filesystem ?? $this->createMock(FilesystemInterface::class);
        $tableexpander = $tableexpander ?? new TableGroupExpander();

        $command = new PutCommand($config, $this->getDatabaseFactoryMock($database), $this->getStorageFactoryMock($storage), $this->getFilesystemFactoryMock($filesystem) , $tableexpander);

        return new CommandTester($command);
    }
}
