<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\Config\Option;
use Meanbee\Magedbm2\Application\ConfigFileResolver;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\FilesystemFactory;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class ConfigureCommand extends BaseCommand
{
    const RETURN_CODE_SAVE_ERROR = 1;
    const NAME = 'configure';

    const ARG_CONFIG_FILE = 'config-file';

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
     * @param ConfigFileResolver $configFileResolver
     * @param FilesystemFactory $filesystemFactory
     * @param Yaml $yaml
     * @param array|null $excluded_options
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFileResolver $configFileResolver,
        FilesystemFactory $filesystemFactory,
        Yaml $yaml,
        array $excluded_options = null
    ) {
        parent::__construct($config, self::NAME);

        $this->config = $config;
        $this->filesystem = $filesystemFactory->create();
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
        parent::configure();

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

        $this->addArgument(
            self::ARG_CONFIG_FILE,
            InputArgument::OPTIONAL,
            'The configuration file to manage.'
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($parentExitCode = parent::execute($input, $output)) !== self::RETURN_CODE_SUCCESS) {
            return $parentExitCode;
        }

        $style = new SymfonyStyle($input, $output);

        $data = [];

        $configFileArgument = $this->input->getArgument(self::ARG_CONFIG_FILE);

        if ($this->input->isInteractive()) {
            $style->note('MageDBM2 uses a number of files to manage its configuration, merging them together in a specific order to support managing multiple projects on a single machine. Consult the documentation for details.');

            if ($configFileArgument === null) {
                $configurationFile = $style->askQuestion(new ChoiceQuestion(
                    'Which of these configuration files would you like to edit?',
                    [
                        $this->configFileResolver->getUserFilePath(),
                        $this->configFileResolver->getProjectFilePath()
                    ]
                ));
            } else {
                $configurationFile = $configFileArgument;
            }
        } else {
            if ($configFileArgument === null) {
                throw new \InvalidArgumentException("When in non-interactive mode, a configuration file must be provided.");
            }

            $configurationFile = $configFileArgument;
        }

        if (file_exists($configurationFile)) {
            $fileContents = file_get_contents($configurationFile);

            $style->text(sprintf(
                '%s currently looks like this:',
                $configurationFile
            ));

            $style->block($fileContents);
        }

        $style->text('<info>Please provide your new values for the following options:</info>');

        foreach (Option::allowUserToPersist() as $optionName) {
            $currentValue = $this->config->get($optionName, true);

            if ($input->isInteractive()) {
                if ($currentValue) {
                    $question = new Question(sprintf('%s (currently: %s): ', $optionName, $currentValue));
                } else {
                    $question = new Question(sprintf('%s: ', $optionName));
                }

                $question->setValidator(function ($value) {
                    return $value;
                });

                $value = $style->askQuestion($question) ?: null;
            } else {
                $value = $this->input->getOption($optionName);
            }

            if ($value !== null) {
                $data[$optionName] = $value;
            }
        }

        $style->table(
            ['Name', 'Value'],
            array_map(function ($k, $v) {
                return [new TableCell($k), new TableCell($v)];
            }, array_keys($data), array_values($data))
        );

        if ($this->input->isInteractive()) {
            if (!$style->confirm(sprintf('Are you sure you want to write these configuration values to %s?', $configurationFile), false)) {
                return 0;
            }
        }

        $yaml = $this->yaml->dump($data);

        if ($this->filesystem->write($configurationFile, $yaml)) {
            $output->writeln(sprintf(
                "<info>Configuration saved in %s.</info>",
                $configurationFile
            ));

            return static::RETURN_CODE_SUCCESS;
        } else {
            $output->writeln(sprintf(
                "<error>Failed to save configuration in %s!</error>",
                $configurationFile
            ));

            return static::RETURN_CODE_SAVE_ERROR;
        }
    }
}
