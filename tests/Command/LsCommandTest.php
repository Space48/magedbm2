<?php

namespace Meanbee\Magedbm2\Tests;

use Meanbee\Magedbm2\Command\LsCommand;
use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LsCommandTest extends TestCase
{
    /**
     * Test that the command lists available projects by default.
     *
     * @test
     */
    public function testListProjects()
    {
        $projects = ["test-project-1", "test-project-2"];

        $storage = $this->createMock(StorageInterface::class);

        $storage
            ->expects($this->once())
            ->method("listProjects")
            ->willReturn($projects);

        $tester = $this->getCommandTester($storage);
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

        $storage = $this->createMock(StorageInterface::class);

        $storage
            ->expects($this->once())
            ->method("listFiles")
            ->willReturn($files);

        $tester = $this->getCommandTester($storage);
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
     *
     * @return CommandTester
     */
    protected function getCommandTester($storage)
    {
        $command = new LsCommand($storage);

        $tester = new CommandTester($command);

        return $tester;
    }
}
