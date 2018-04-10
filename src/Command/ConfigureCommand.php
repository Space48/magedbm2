<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigFileResolver;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ConfigureCommand extends BaseCommand
{
    const RETURN_CODE_SAVE_ERROR = 1;
    const NAME                   = 'configure';

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
        "project-config",
        "db-host", "db-port", "db-user", "db-pass", "db-name"
    ];

    /**
     * @var ConfigFileResolver
     */
    private $configFileResolver;

    /**
     * @param ConfigInterface $config
     * @param FilesystemInterface $filesystem
     * @param Yaml $yaml
     * @param array|null $excluded_options
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFileResolver $configFileResolver,
        FilesystemInterface $filesystem,
        Yaml $yaml,
        array $excluded_options = null
    ) {
        parent::__construct($config, self::NAME);

        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->yaml = $yaml;

        if ($excluded_options) {
            $this->excluded_options = $excluded_options;
        }

        $this->configFileResolver = $configFileResolver;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
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
        if (($parentExitCode = parent::execute($input, $output)) !== self::RETURN_CODE_NO_ERROR) {
            return $parentExitCode;
        }

        $options = $this->getConfigOptions();
        $data = [];
        $file = $this->configFileResolver->getUserFilePath();

        if (file_exists($file)) {
            $this->output->writeln(sprintf(
                '<info>Your current configuration file (%s) has the following content:</info>',
                $file
            ));

            $this->output->writeln(file_get_contents($file));
        }

        $this->output->writeln('<info>Please provide your new values for the following options:</info>');

        /** @var QuestionHelper $question_helper */
        $question_helper = $this->getHelper("question");

        foreach ($options as $option) {
            $name = $option->getName();
            $value = $this->config->get($name, true);

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
