<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Service\ServiceException;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RmCommand extends Command
{
    const RETURN_CODE_NO_ERROR = 0;
    const RETURN_CODE_STORAGE_ERROR = 1;

    /** @var StorageInterface */
    protected $storage;

    public function __construct(StorageInterface $storage)
    {
        parent::__construct();

        $this->storage = $storage;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName("rm")
            ->setDescription("Delete uploaded backup files.")
            ->addArgument(
                "project",
                InputArgument::REQUIRED,
                "Project identifier."
            )
            ->addArgument(
                "file",
                InputArgument::REQUIRED,
                "File to delete."
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $input->getArgument("project");
        $file = $input->getArgument("file");

        try {
            $this->storage->delete($project, $file);
        } catch (ServiceException $e) {
            $output->writeln(sprintf(
                "<error>Failed to delete '%s' from '%s': %s",
                $file,
                $project,
                $e->getMessage()
            ));

            return static::RETURN_CODE_STORAGE_ERROR;
        }

        $output->writeln(sprintf(
            "<info>Deleted '%s' from '%s'.</info>",
            $file,
            $project
        ));

        return static::RETURN_CODE_NO_ERROR;
    }
}
