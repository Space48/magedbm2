<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Exception\ServiceException;
use Meanbee\Magedbm2\Service\StorageFactory;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RmCommand extends BaseCommand
{
    const RETURN_CODE_STORAGE_ERROR = 1;

    const ARG_TYPE    = "type";
    const ARG_PROJECT = "project";
    const ARG_FILE    = "file";
    const NAME        = "rm";

    const TYPE_DATABASE = 'database';
    const TYPE_EXPORT   = 'export';

    /** @var StorageInterface */
    protected $storage;

    /**
     * @param ConfigInterface $config
     * @param StorageFactory $storageFactory
     */
    public function __construct(ConfigInterface $config, StorageFactory $storageFactory)
    {
        parent::__construct($config, self::NAME);

        $this->storage = $storageFactory->create();
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
            ->setDescription("Delete uploaded backup files.")
            ->addArgument(
                self::ARG_TYPE,
                InputArgument::REQUIRED,
                "File type (database or export)"
            )
            ->addArgument(
                self::ARG_PROJECT,
                InputArgument::REQUIRED,
                "Project identifier."
            )
            ->addArgument(
                self::ARG_FILE,
                InputArgument::REQUIRED,
                "File to delete."
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

        $type = $input->getArgument(self::ARG_TYPE);
        $project = $input->getArgument(self::ARG_PROJECT);
        $file = $input->getArgument(self::ARG_FILE);

        if ($type === self::TYPE_DATABASE) {
            $this->storage->setPurpose(StorageInterface::PURPOSE_STRIPPED_DATABASE);
        } elseif ($type === self::TYPE_EXPORT) {
            $this->storage->setPurpose(StorageInterface::PURPOSE_ANONYMISED_DATA);
        } else {
            throw new \InvalidArgumentException(
                "The argument 'type' supports the following values: " . implode(', ', [
                    self::TYPE_DATABASE,
                    self::TYPE_EXPORT
                ])
            );
        }

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

        return static::RETURN_CODE_SUCCESS;
    }
}
