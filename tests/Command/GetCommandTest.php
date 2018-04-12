<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Command\GetCommand;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class GetCommandTest extends AbstractCommandTest
{
    /**
     * Test that the command attempts to import a backup file.
     *
     * @test
     */
    public function testImport()
    {
        $database = $this->getDatabaseMock();
        $database
            ->expects($this->once())
            ->method("import")
            ->with($this->equalTo("/tmp/backup-file.sql.gz"));

        $storage = $this->getStorageMock();
        $storage->method("download")->willReturn("/tmp/backup-file.sql.gz");

        $filesystem = $this->getFilesystemMock();

        $tester = $this->getCommandTester($database, $storage, $filesystem, true);
        $tester
            ->execute([
                "project" => "test",
                "file"    => "backup-file.sql.gz",
            ]);
    }

    /**
     * Test that --download-only moves a file and does not import.
     *
     * @test
     */
    public function testDownloadOnly()
    {
        $database = $this->getDatabaseMock();
        $database
            ->expects($this->never())
            ->method("import");

        $storage = $this->getStorageMock();
        $storage->method("download")->willReturn("/tmp/backup-file.sql.gz");

        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method("move")
            ->willReturn(true);

        $tester = $this->getCommandTester($database, $storage, $filesystem, true);
        $tester
            ->execute([
                "project"         => "Test",
                "file"            => "backup-file.sql.gz",
                "--download-only" => true,
            ]);
    }

    /**
     * Test that a confirmation is required to proceed with an import.
     *
     * @test
     */
    public function testConfirmation()
    {
        $database = $this->getDatabaseMock();
        $database
            ->expects($this->never())
            ->method("import");

        $storage = $this->getStorageMock();
        $filesystem = $this->getFilesystemMock();

        $tester = $this->getCommandTester($database, $storage, $filesystem, false);
        $tester
            ->execute([
                "project" => "test",
                "file"    => "backup-file.sql.gz",
            ]);
    }

    /**
     * Test that no confirmation is presented when --force flag is used.
     *
     * @test
     */
    public function testForce()
    {
        $database = $this->getDatabaseMock();
        $database
            ->expects($this->once())
            ->method("import");

        $storage = $this->getStorageMock();
        $filesystem = $this->getFilesystemMock();

        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project" => "test",
            "file"    => "backup-file.sql.gz",
            "--force" => true,
        ]);
    }

    /**
     * Test that the command imports the latest file when none provided.
     *
     * @test
     */
    public function testLatestFile()
    {
        $database = $this->getDatabaseMock();

        $storage = $this->getStorageMock();
        $storage
            ->expects($this->once())
            ->method("getLatestFile");

        $filesystem = $this->createMock(FilesystemInterface::class);

        $tester = $this->getCommandTester($database, $storage, $filesystem);
        $tester->execute([
            "project" => "test",
            "--force" => true,
        ]);

    }

    /**
     * Create and configure a tester for the "get" command.
     *
     * @param DatabaseInterface   $database
     * @param StorageInterface    $storage
     * @param FilesystemInterface $filesystem
     *
     * @return CommandTester
     */
    protected function getCommandTester($database, $storage, $filesystem, $confirmation = false)
    {
        $command = new GetCommand($this->getConfigMock(), $database, $storage, $filesystem);

        $helper = $this->createMock(QuestionHelper::class);
        $helper
            ->method("ask")
            ->willReturn($confirmation);

        $command->setHelperSet(new HelperSet([
            "question" => $helper,
        ]));

        $tester = new CommandTester($command);

        return $tester;
    }
}
