<?php

namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\ConfigFileResolver;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Command\ConfigureCommand;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use VirtualFileSystem\FileSystem as VirtualFileSystem;

class ConfigureCommandTest extends AbstractCommandTest
{
    /**
     * @var VirtualFileSystem
     */
    private $vfs;

    /**
     * @var string
     */
    private $configPath;

    protected function setUp(): void
    {
        $this->vfs = new VirtualFileSystem();
        $this->configPath = $this->vfs->path('/example.yml');
    }

    /**
     * Test that the command saves the configuration correctly in interactive mode.
     *
     * @test
     */
    public function testInteractive()
    {
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method("write")
            ->with(
                $this->equalTo($this->configPath),
                $this->equalTo("db_host: 127.0.0.1\ndb_port: '3333'\n")
            );

        $tester = $this->getCommandTester($filesystem);
        $tester->setInputs([
            0, // File selection,
            '127.0.0.1',
            '',
            '',
            '',
            '3333',
            '',
            '',
            '',
            '',
            'yes' // Confirm write
        ]);

        $tester->execute([''], ["interactive" => true]);
    }

    /**
     * Test that the command saves the configuration correctly in non-interactive mode.
     *
     * @test
     */
    public function testNonInteractive()
    {
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method("write")
            ->with(
                $this->equalTo($this->configPath),
                $this->equalTo("db_host: 127.0.0.1\ndb_port: '3333'\n")
            );

        $tester = $this->getCommandTester($filesystem);
        $tester->execute([
            'config-file' => $this->configPath,
            '--db-host' => '127.0.0.1',
            '--db-port' => '3333'
        ], ["interactive" => false]);
    }

    /**
     * Create and configure a tester for the "configure" command.
     *
     * @param FilesystemInterface $filesystem
     *
     * @return CommandTester
     */
    protected function getCommandTester($filesystem)
    {
        // Create an input definition mock to provide available config options
        $definition = new InputDefinition([
            new InputOption("test-option", null, InputOption::VALUE_REQUIRED, "Test Option"),
            new InputOption("other-option", null, InputOption::VALUE_REQUIRED, "Other Option"),
        ]);

        // Create a question helper to provide responses to interactive input prompts
        $question_helper = $this->createMock(QuestionHelper::class);
        $question_helper
            ->method("ask")
            ->willReturnCallback(function ($input, $output, $question) {
                /** @var Question $question */
                switch ($question->getQuestion()) {
                    case "Test Option: ":
                        return "test-option-value";
                    case "Other Option: ":
                        return "other-option-value";
                    default:
                        throw new \Exception("Unexpected interactive prompt!");
                }
            });

        // Create an application mock to provide input definition and helper set
        $application = $this->createMock(Application::class);
        $application
            ->method("getDefinition")
            ->willReturn($definition);
        $application
            ->method("getHelperSet")
            ->willReturn(new HelperSet([
                "question" => $question_helper,
            ]));

        // Create a config mock to return the config file path and default config values
        $config = $this->getConfigMock();
        $config
            ->method("get")
            ->willReturnCallback(function ($option) {
                switch ($option) {
                    case "test-option":
                        return "default-test-option-value";
                    default:
                        return null;
                }
            });

        $configResolver = $this->createMock(ConfigFileResolver::class);
        $configResolver->method('getUserFilePath')
            ->willReturn($this->configPath);

        $command = new ConfigureCommand($config, $configResolver, $this->getFilesystemFactoryMock($filesystem), new Yaml());
        $command->setApplication($application);

        return new CommandTester($command);
    }
}
