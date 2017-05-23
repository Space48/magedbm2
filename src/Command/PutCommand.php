<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\ServiceException;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PutCommand extends Command
{
    const RETURN_CODE_NO_ERROR = 0;
    const RETURN_CODE_DATABASE_ERROR = 1;
    const RETURN_CODE_STORAGE_ERROR = 2;

    /** @var DatabaseInterface */
    protected $database;

    /** @var StorageInterface */
    protected $storage;

    /** @var FilesystemInterface */
    protected $filesystem;

    public function __construct(DatabaseInterface $database, StorageInterface $storage, FilesystemInterface $filesystem)
    {
        parent::__construct();

        $this->database = $database;
        $this->storage = $storage;
        $this->filesystem = $filesystem;
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
                "List of tables to export without any data. By default, all customer data is stripped."
            )
            ->addOption(
                "clean",
                "c",
                InputOption::VALUE_REQUIRED,
                "The number of latest backup files to keep when uploading. Default: 5."
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
        $project = $input->getArgument("project");
        $strip_tables = $input->getOption("strip") ?: "@development";

        try {
            $local_file = $this->database->dump($project, $strip_tables);
        } catch (ServiceException $e) {
            $output->writeln(sprintf(
                "<error>Failed to create a database backup file: %s</error>",
                $e->getMessage()
            ));

            return static::RETURN_CODE_DATABASE_ERROR;
        }

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
            $clean = $input->getOption("clean") ?: 5;

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
}
