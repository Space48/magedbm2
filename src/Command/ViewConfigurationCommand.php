<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ViewConfigurationCommand extends BaseCommand
{
    const NAME = 'view-config';

    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config, self::NAME);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($parentExitCode = parent::execute($input, $output)) !== self::RETURN_CODE_SUCCESS) {
            return $parentExitCode;
        }

        $keys = $this->config->all();

        $output->write(Yaml::dump($keys, Yaml::DUMP_OBJECT_AS_MAP));

        return static::RETURN_CODE_SUCCESS;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::NAME)
            ->setDescription('View the current configuration');
    }
}
