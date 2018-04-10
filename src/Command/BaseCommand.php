<?php

namespace Meanbee\Magedbm2\Command;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Application\ConfigLoader\InputLoader;
use Meanbee\Magedbm2\Service\ConfigurableServiceInterface;
use Meanbee\Magedbm2\Exception\ConfigurationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command implements LoggerAwareInterface
{
    const RETURN_CODE_NO_ERROR = 0;
    const RETURN_CODE_CONFIGURATION_ERROR = 100;

    private $servicesToValidate = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param ConfigInterface $config
     * @param $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ConfigInterface $config, $name)
    {
        parent::__construct($name);

        $this->logger = new NullLogger();
        $this->config = $config;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConfigurationException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->loadAdditionalConfig();

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
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

    /**
     * @throws ConfigurationException
     */
    private function loadAdditionalConfig()
    {
        $this->logger->info('Loading config from input');
        $this->config->merge((new InputLoader($this->input))->asConfig());
    }
}
