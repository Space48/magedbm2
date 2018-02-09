<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\ServiceException;
use Meanbee\Magedbm2\Service\StorageInterface;
use Meanbee\Magedbm2\Service\TableExpanderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PutCommand extends BaseCommand
{
    const RETURN_CODE_DATABASE_ERROR = 1;
    const RETURN_CODE_STORAGE_ERROR = 2;

    /** @var DatabaseInterface */
    protected $database;

    /** @var StorageInterface */
    protected $storage;

    /** @var FilesystemInterface */
    protected $filesystem;

    /** @var TableExpanderInterface */
    protected $tableExpander;
    
    /** @var ConfigInterface */
    protected $config;
    
    public function __construct(ConfigInterface $config, DatabaseInterface $database, StorageInterface $storage, FilesystemInterface $filesystem, TableExpanderInterface $tableExpander)
    {
        parent::__construct();

        $this->database = $database;
        $this->storage = $storage;
        $this->filesystem = $filesystem;
        $this->tableExpander = $tableExpander;
        $this->config = $config;
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
            ->setName("put")
            ->setDescription("Create and upload a database backup.")
            ->addArgument(
                "project",
                InputArgument::REQUIRED,
                "Project identifier."
            )
            ->addOption(
                "strip",
                "s",
                InputOption::VALUE_OPTIONAL,
                "List of space-separated tables to export without any data. By default, all customer data is stripped.",
                "@development"
            )
            ->addOption(
                "clean",
                "c",
                InputOption::VALUE_REQUIRED,
                "The number of latest backup files to keep when uploading.",
                5
            )
            ->addOption(
                "no-clean",
                "C",
                InputOption::VALUE_NONE,
                "Do not remove old backup files after uploading."
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setHelp($this->getHelpText());

        if (($parentExitCode = parent::execute($input, $output)) !== self::RETURN_CODE_NO_ERROR) {
            return $parentExitCode;
        }

        if ($tableGroups = $this->config->getTableGroups()) {
            $this->tableExpander->setTableGroups($tableGroups);
        }
        
        $project = $input->getArgument("project");
        $strip_tables = $input->getOption("strip") ?? '@development';

        $output->writeln(
            "<info>Creating a backup file of the database...</info>",
            OutputInterface::VERBOSITY_VERBOSE
        );

        try {
            $this->database->setLogger($this->getLogger());

            $local_file = $this->database->dump($project, $this->tableExpander->expand($strip_tables));
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

        if (!$input->getOption("no-clean")) {
            $clean = $input->getOption("clean");

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
        
        if (count($tableGroups) > 0) {
            return <<<HELP
The following table groups are configured:

$tableGroups
HELP;

        } else {
            return <<<HELP
There are no table groups configured. You can configure table groups in the configuration files.
HELP;

        }
    }
}
