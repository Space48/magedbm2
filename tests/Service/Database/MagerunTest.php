<?php

namespace Meanbee\Magedbm2\Tests\Service\Database;

use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Service\Database\Magerun;
use N98\Magento\Command\Database\DumpCommand;
use N98\Magento\Command\Database\ImportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

class MagerunTest extends TestCase
{
    /**
     * Test that the database import command is correctly passed into Magerun.
     *
     * @test
     */
    public function testImport()
    {
        $app = new Application();

        $command = $this->createMock(ImportCommand::class);
        $command
            ->expects($this->once())
            ->method("run")
            ->with(
                $this->equalTo(new ArrayInput([
                    "filename"      => "/tmp/test/backup-file.sql.gz",
                    "--compression" => "gzip",
                ]))
            );

        $magerun = $this->createMock(\N98\Magento\Application::class);
        $magerun
            ->method("get")
            ->willReturn($command);

        $service = new Magerun($app, null, $magerun);

        $service->import("/tmp/test/backup-file.sql.gz");
    }

    /**
     * Test that the database dump command is correctly passed into Magerun.
     *
     * @test
     */
    public function testDump()
    {
        $app = new Application();

        $config = $this->createMock(Application\ConfigInterface::class);
        $config
            ->method("getTmpDir")
            ->willReturn("/tmp/test");

        $command = $this->createMock(DumpCommand::class);
        $command
            ->expects($this->once())
            ->method("run")
            ->with($this->callback(function (ArrayInput $input) {
                return preg_match(
                        "#^/tmp/test/test-identifier-[0-9-_]+.sql.gz$#",
                        $input->getParameterOption("filename")
                    )
                    && $input->getParameterOption("--strip") === "test_table other_table"
                    && $input->getParameterOption("--compression") === "gzip"
                    && $input->getParameterOption("--add-routines") === true;
            }));

        $magerun = $this->createMock(\N98\Magento\Application::class);
        $magerun
            ->method("get")
            ->willReturn($command);

        $service = new Magerun($app, $config, $magerun);

        $service->dump("test-identifier", "test_table other_table");
    }
}
