<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Command\LsCommand;
use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Tester\CommandTester;

class LsCommandTest extends AbstractCommandTest
{
    /**
     * Test that the command lists available projects by default.
     *
     * @test
     */
    public function testListProjects()
    {
        $projects = ["test-project-1", "test-project-2"];

        $storage = $this->getStorageMock();
        $storage->setPurpose(StorageInterface::PURPOSE_STRIPPED_DATABASE);

        $dataStorage = $this->getStorageMock();
        $dataStorage->setPurpose(StorageInterface::PURPOSE_ANONYMISED_DATA);

        $storage
            ->expects($this->once())
            ->method("listProjects")
            ->willReturn($projects);

        $storage->method('validateConfiguration')
            ->willReturn(true);

        $dataStorage
            ->expects($this->once())
            ->method("listProjects")
            ->willReturn($projects);

        $dataStorage->method('validateConfiguration')
            ->willReturn(true);

        $tester = $this->getCommandTester($storage, $dataStorage);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertContains("Available projects", $output);
        foreach ($projects as $project) {
            $this->assertContains($project, $output);
        }
    }

    /**
     * Test that the command lists available files when the project is specified.
     *
     * @test
     */
    public function testListFiles()
    {
        $files = ["test-file-1", "test-file-2"];

        $files = array_map(function ($name) {
            $file = new File();

            $file->name = $name;
            $file->project = "test";
            $file->size = (rand(1, 100) / 10) * 1024 * 1024;
            $file->last_modified = new \DateTime();

            return $file;
        }, $files);

        $storage = $this->getStorageMock();
        $storage->setPurpose(StorageInterface::PURPOSE_STRIPPED_DATABASE);

        $dataStorage = $this->getStorageMock();
        $dataStorage->setPurpose(StorageInterface::PURPOSE_ANONYMISED_DATA);

        $storage
            ->expects($this->once())
            ->method("listFiles")
            ->willReturn($files);

        $storage->method('validateConfiguration')
            ->willReturn(true);

        $dataStorage
            ->expects($this->once())
            ->method("listFiles")
            ->willReturn($files);

        $dataStorage->method('validateConfiguration')
            ->willReturn(true);

        $tester = $this->getCommandTester($storage, $dataStorage);
        $tester->execute([
            "project" => "test",
        ]);

        $output = $tester->getDisplay();

        $this->assertContains("Available files", $output);

        foreach ($files as $file) {
            $this->assertContains($file->name, $output);
        }
    }

    /**
     * Create and configure a tester for the "ls" command.
     *
     * @param StorageInterface $storage
     * @param StorageInterface $dataStorage
     *
     * @return CommandTester
     */
    protected function getCommandTester($storage, $dataStorage)
    {
        $command = new LsCommand($this->getConfigMock(), $storage, $dataStorage);

        return new CommandTester($command);
    }
}
