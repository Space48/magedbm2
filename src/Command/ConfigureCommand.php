<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ConfigureCommand extends Command
{
    const RETURN_CODE_NO_ERROR = 0;
    const RETURN_CODE_SAVE_ERROR = 1;

    /** @var ConfigInterface */
    protected $config;

    /** @var FilesystemInterface */
    protected $filesystem;

    /** @var Yaml */
    protected $yaml;

    /**
     * Application options excluded from interactive configuration.
     *
     * @var string[]
     */
    protected $excluded_options = [
        "help", "version",
        "quiet", "verbose", "no-interaction",
        "ansi", "no-ansi",
        "config", "root-dir",
    ];

    public function __construct(
        ConfigInterface $config,
        FilesystemInterface $filesystem,
        Yaml $yaml,
        array $excluded_options = null
    ) {
        parent::__construct();

        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->yaml = $yaml;

        if ($excluded_options) {
            $this->excluded_options = $excluded_options;
        }
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription("Create or update the application configuration file.")
            ->setHelp(<<<HELP
Saves application options to a configuration file to allow running commands
without having to provide credentials and configuration options. By default
the command runs in interactive mode, providing prompts for each configuration
option. If executed with the `-n` flag, will save the option values provided
to the command, or the default values if none provided.
HELP
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->getConfigOptions();
        $data = [];

        /** @var QuestionHelper $question_helper */
        $question_helper = $this->getHelper("question");

        foreach ($options as $option) {
            $name = $option->getName();
            $value = $this->config->get($name);

            if ($input->isInteractive()) {
                $value = $question_helper->ask(
                    $input,
                    $output,
                    new Question(sprintf("%s: ", $option->getDescription()))
                );
            }

            $data[$name] = $value;
        }

        $yaml = $this->yaml->dump($data);
        $file = $this->config->getConfigFile();

        if ($this->filesystem->write($file, $yaml)) {
            $output->writeln(sprintf(
                "<info>Configuration saved in %s.</info>",
                $file
            ));

            return static::RETURN_CODE_NO_ERROR;
        } else {
            $output->writeln(sprintf(
                "<error>Failed to save configuration in %s!</error>",
                $file
            ));

            return static::RETURN_CODE_SAVE_ERROR;
        }
    }

    /**
     * Get the options available for configuration.
     *
     * @return InputOption[]
     */
    protected function getConfigOptions()
    {
        if ($app = $this->getApplication()) {
            return array_filter($app->getDefinition()->getOptions(), function (InputOption $option) {
                return !in_array($option->getName(), $this->excluded_options);
            });
        }

        return [];
    }
}
