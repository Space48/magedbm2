<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\ServiceException;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GetCommand extends BaseCommand
{
    const RETURN_CODE_DOWNLOAD_ERROR = 1;
    const RETURN_CODE_FILESYSTEM_ERROR = 2;
    const RETURN_CODE_DATABASE_ERROR = 3;

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
        $this
            ->setName("get")
            ->setDescription("Download and import a database backup.")
            ->addArgument(
                "project",
                InputArgument::REQUIRED,
                "Project identifier."
            )
            ->addArgument(
                "file",
                InputArgument::OPTIONAL,
                "Backup file to import. If not specified, imports the latest available file."
            )
            ->addOption(
                "download-only",
                "o",
                InputOption::VALUE_NONE,
                "Output the back up file into the current directory, instead of importing it."
            )
            ->addOption(
                "force",
                "f",
                InputOption::VALUE_NONE,
                "Skip database import confirmation."
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

        $project = $input->getArgument("project");
        $file = $input->getArgument("file");

        // Ask for confirmation before overwriting existing database data
        if (!$input->getOption("force") && !$input->getOption("download-only")) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper("question");
            $question = new ConfirmationQuestion(
                "Are you sure you with to overwrite the local database? [y/N] ",
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return static::RETURN_CODE_NO_ERROR;
            }
        }

        try {
            if (!$file) {
                $file = $this->storage->getLatestFile($project);
            }

            $output->writeln(
                sprintf("<info>Downloading backup file %s...</info>", $file),
                OutputInterface::VERBOSITY_VERBOSE
            );

            $local_file = $this->storage->download($project, $file);
        } catch (ServiceException $e) {
            $output->writeln(sprintf("<error>Failed to download the backup file: %s</error>", $e->getMessage()));

            return static::RETURN_CODE_DOWNLOAD_ERROR;
        }

        if ($input->getOption("download-only")) {
            $output_file = getcwd() . DIRECTORY_SEPARATOR . $file;

            if ($this->filesystem->move($local_file, $output_file)) {
                $output->writeln(sprintf("<info>Backup file downloaded to %s</info>", $output_file));
            } else {
                $output->writeln(sprintf(
                    "<error>Failed to move the downloaded backup file. Backup saved in %s</error>",
                    $local_file
                ));

                return static::RETURN_CODE_FILESYSTEM_ERROR;
            }
        } else {
            $output->writeln(
                "<info>Importing downloaded backup file into the database...</info>",
                OutputInterface::VERBOSITY_VERBOSE
            );

            try {
                $this->database->import($local_file);
            } catch (ServiceException $e) {
                $output->writeln(sprintf(
                    "<error>Failed to import backup file '%s': %s</error>",
                    $file,
                    $e->getMessage()
                ));
                $this->filesystem->delete($local_file);

                return static::RETURN_CODE_DATABASE_ERROR;
            }
            $output->writeln(sprintf("<info>Backup %s downloaded and imported into the database.</info>", $file));
        }

        $this->filesystem->delete($local_file);

        return static::RETURN_CODE_NO_ERROR;
    }
}
