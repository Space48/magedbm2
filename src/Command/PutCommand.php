<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\Config\Option;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Helper\TableGroupExpander;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Exception\ServiceException;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PutCommand extends BaseCommand
{
    const RETURN_CODE_DATABASE_ERROR = 1;
    const RETURN_CODE_STORAGE_ERROR = 2;

    const ARG_PROJECT = "project";
    const NAME        = "put";

    /** @var DatabaseInterface */
    protected $database;

    /** @var StorageInterface */
    protected $storage;

    /** @var FilesystemInterface */
    protected $filesystem;

    /** @var TableGroupExpander */
    protected $tableExpander;

    /**
     * @param ConfigInterface $config
     * @param DatabaseInterface $database
     * @param StorageInterface $storage
     * @param FilesystemInterface $filesystem
     * @param TableGroupExpander $tableGroupExpander
     */
    public function __construct(
        ConfigInterface $config,
        DatabaseInterface $database,
        StorageInterface $storage,
        FilesystemInterface $filesystem,
        TableGroupExpander $tableGroupExpander = null
    ) {
        $this->database = $database;
        $this->storage = $storage;
        $this->filesystem = $filesystem;
        $this->tableExpander = $tableGroupExpander ?? new TableGroupExpander();
        $this->config = $config;

        $storage->setPurpose(StorageInterface::PURPOSE_STRIPPED_DATABASE);

        parent::__construct($config, self::NAME);

        $this->ensureServiceConfigurationValidated('database', $this->database);
        $this->ensureServiceConfigurationValidated('storage', $this->storage);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription("Create and upload a database backup.")
            ->addArgument(
                self::ARG_PROJECT,
                InputArgument::REQUIRED,
                "Project identifier."
            )
            ->addOption(
                Option::STRIP,
                "s",
                InputOption::VALUE_OPTIONAL,
                "List of space-separated tables / table groups to export without any data. By default, all " .
                    "customer data is stripped.",
                "@development"
            )
            ->addOption(
                Option::CLEAN_COUNT,
                "c",
                InputOption::VALUE_REQUIRED,
                "The number of latest backup files to keep when uploading.",
                5
            )
            ->addOption(
                Option::NO_CLEAN,
                "C",
                InputOption::VALUE_NONE,
                "Do not remove old backup files after uploading."
            );

        $this->setHelp($this->getHelpText());
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($parentExitCode = parent::execute($input, $output)) !== self::RETURN_CODE_NO_ERROR) {
            return $parentExitCode;
        }

        if ($tableGroups = $this->config->getTableGroups()) {
            $this->tableExpander->setTableGroups($tableGroups);
        }

        $project = $input->getArgument(self::ARG_PROJECT);
        $strip_tables = $input->getOption(Option::STRIP) ?? '@development';

        $output->writeln(
            "<info>Creating a backup file of the database...</info>",
            OutputInterface::VERBOSITY_VERBOSE
        );

        try {
            $this->database->setLogger($this->getLogger());

            $local_file = $this->database->dump($project, $this->tableExpander->expand($strip_tables));

            if (!file_exists($local_file)) {
                throw new ServiceException(sprintf(
                    "No file was created from database service. (expected %s)",
                    $local_file
                ));
            }

            if (!is_readable($local_file)) {
                throw new ServiceException("File was created from database service, but it wasn't readable.");
            }
        } catch (ServiceException $e) {
            $output->writeln(sprintf(
                "<error>Failed to create a database backup file: %s</error>",
                $e->getMessage()
            ));

            return static::RETURN_CODE_DATABASE_ERROR;
        }

        $output->writeln(
            sprintf("<info>Uploading the backup file to project %s...", $project),
            OutputInterface::VERBOSITY_VERBOSE
        );

        try {
            $uploaded_file = $this->storage->upload($project, $local_file);
        } catch (ServiceException $e) {
            $output->writeln(sprintf(
                "<error>Failed to upload the database backup file: %s</error>",
                $e->getMessage()
            ));

            return static::RETURN_CODE_STORAGE_ERROR;
        }

        $output->writeln(sprintf(
            "<info>Database backup uploaded to '%s'.</info>",
            $uploaded_file
        ));

        $this->filesystem->delete($local_file);

        if (!$input->getOption(Option::NO_CLEAN)) {
            $clean = $input->getOption(Option::CLEAN_COUNT);

            try {
                $this->storage->clean($project, $clean);
            } catch (ServiceException $e) {
                $output->writeln(sprintf(
                    "<error>Failed to delete old database backup files: %s</error>",
                    $e->getMessage()
                ));

                return static::RETURN_CODE_STORAGE_ERROR;
            }
        }

        return static::RETURN_CODE_NO_ERROR;
    }

    /**
     * @return string
     */
    private function getHelpText()
    {
        $tableGroups = $this->config->getTableGroups();

        if ($tableGroups && count($tableGroups) > 0) {
            $tableGroupHelp =
                "The following table groups are configured and can be used with the <info>--strip</info> option:\n\n";


            foreach ($tableGroups as $tableGroup) {
                $tableGroupHelp .= sprintf(
                    "<info>%s</info>: <comment>%s</comment>\n",
                    $tableGroup->getId(),
                    $tableGroup->getDescription()
                );

                $tableGroupHelp .= "\t" . implode(', ', $tableGroup->getTables()) . "\n";
            }
        } else {
            $tableGroupHelp =
                'There are no table groups configured. You can configure table groups in the configuration files.';
        }

        return $this->getDescription() . "\n\n" . $tableGroupHelp;
    }
}
