<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Service\ConfigurableServiceInterface;
use Meanbee\Magedbm2\Service\ConfigurationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    const RETURN_CODE_NO_ERROR = 0;
    const RETURN_CODE_CONFIGURATION_ERROR = 100;

    private $servicesToValidate = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        $serviceExceptions = $this->validateServices();

        if (count($serviceExceptions) > 0) {
            foreach ($serviceExceptions as $serviceName => $serviceException) {
                $output->writeln(sprintf(
                    '<error>There was a problem with the %s adapter: %s</error>',
                    $serviceName,
                    $serviceException->getMessage()
                ));
            }

            return self::RETURN_CODE_CONFIGURATION_ERROR;
        }

        return self::RETURN_CODE_NO_ERROR;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $name
     * @param ConfigurableServiceInterface $service
     */
    protected function ensureServiceConfigurationValidated(string $name, ConfigurableServiceInterface $service)
    {
        $this->servicesToValidate[$name] = $service;
    }

    /**
     * Validate services, returning their exceptions. If there are any exceptions then the exception will be keyed with
     * the name of the service type, e.g.
     *
     * [
     *     'database' => new \Exception('This is the exception that was raised from the database service')
     * ]
     *
     * @return array
     */
    private function validateServices(): array
    {
        $exceptions = [];

        if (0 === count($this->servicesToValidate)) {
            return $exceptions;
        }

        foreach ($this->servicesToValidate as $name => $adapter) {
            try {
                /** @var ConfigurableServiceInterface $adapter */
                $adapter->validateConfiguration();
            } catch (ConfigurationException $e) {
                $exceptions[$name] = $e;
            }
        }

        return $exceptions;
    }
}
