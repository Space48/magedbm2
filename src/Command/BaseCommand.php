<?php

namespace Meanbee\Magedbm2\Command;

use DI\Container;
use Meanbee\Magedbm2\Application\Config\Option;
use Meanbee\Magedbm2\Application\ConfigFileResolver;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Application\ConfigLoader\FileLoader;
use Meanbee\Magedbm2\Application\ConfigLoader\InputLoader;
use Meanbee\Magedbm2\Service\ConfigurableServiceInterface;
use Meanbee\Magedbm2\Exception\ConfigurationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BaseCommand extends Command implements LoggerAwareInterface
{
    const RETURN_CODE_SUCCESS             = 0;
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
     * @var ConfigFileResolver
     */
    private $configFileResolver;

    /**
     * @param ConfigInterface $config
     * @param $name
     */
    public function __construct(ConfigInterface $config, $name)
    {
        $this->logger = new NullLogger();
        $this->config = $config;

        // Note: we can't use the container to get this as it's not been applied to the object yet.
        $this->configFileResolver = new ConfigFileResolver();

        parent::__construct($name);
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

        /*
         * Load our configuration values in order such that it can come gradually more specific. The parsing of $ARGV
         * has been done by this point, so we can safely read from $input for overrides.
         */
        $this->loadGlobalConfig($input);
        $this->loadProjectConfig($input);
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

        return self::RETURN_CODE_SUCCESS;
    }

    /**
     * Define our global options that can be used by any subcommand.
     */
    protected function configure()
    {
        $this->getDefinition()
            ->addOptions([
                new InputOption(
                    Option::PROJECT_CONFIG_FILE,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    // Note: Don't use the default parameter for the fall back file location as we want to distinguish
                    //       between a user defined override and the actual default.
                    sprintf(
                        "Project configuration file to use (will search for .magedbm2.yml in your current working directory if not specified, currently: %s)", //phpcs:ignore
                        $this->configFileResolver->getProjectFilePath()
                    )
                ),
                new InputOption(
                    Option::GLOBAL_CONFIG_FILE,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    // Note: Don't use the default parameter for the fall back file location as we want to distinguish
                    //       between a user defined override and the actual default.
                    sprintf(
                        "User configuration file to use (will search for ~/.magedbm2/config.yml if not specified, currently: %s)", //phpcs:ignore
                        $this->configFileResolver->getUserFilePath()
                    )
                ),
                new InputOption(
                    Option::DB_HOST,
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Database host'
                ),
                new InputOption(
                    Option::DB_PORT,
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Database port',
                    3306
                ),
                new InputOption(
                    Option::DB_USER,
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Database username'
                ),
                new InputOption(
                    Option::DB_PASS,
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Database password'
                ),
                new InputOption(
                    Option::DB_NAME,
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Database name'
                ),
                new InputOption(
                    Option::DB_SSL_CA,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Path to SSL CA e.g. /etc/ssl/my-cert.pem'
                ),
                new InputOption(
                    Option::ROOT_DIR,
                    null,
                    InputOption::VALUE_REQUIRED,
                    "Magento 2 root directory"
                ),
                new InputOption(
                    Option::STORAGE_ACCESS_KEY,
                    null,
                    InputOption::VALUE_REQUIRED,
                    "S3 Access Key ID"
                ),
                new InputOption(
                    Option::STORAGE_SECRET_KEY,
                    null,
                    InputOption::VALUE_REQUIRED,
                    "S3 Secret Access Key"
                ),
                new InputOption(
                    Option::STORAGE_REGION,
                    null,
                    InputOption::VALUE_REQUIRED,
                    "S3 region"
                ),
                new InputOption(
                    Option::STORAGE_BUCKET,
                    null,
                    InputOption::VALUE_REQUIRED,
                    "S3 bucket for stripped databases"
                ),
                new InputOption(
                    Option::STORAGE_ANONYMISED_BUCKET,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    "S3 bucket for anonymised data exports"
                ),
                new InputOption(
                    Option::STORAGE_ANONYMISED_REGION,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    "S3 region for anonymised data exports"
                )
            ]);
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

    /**
     * @param InputInterface $input
     * @return void
     * @throws ConfigurationException
     */
    private function loadProjectConfig(InputInterface $input)
    {
        return $this->tryConfigLoadFromFiles(
            $input->getOption(Option::PROJECT_CONFIG_FILE),
            $this->configFileResolver->getProjectFilePath()
        );
    }

    /**
     * @param InputInterface $input
     * @return void
     * @throws ConfigurationException
     */
    private function loadGlobalConfig(InputInterface $input)
    {
        return $this->tryConfigLoadFromFiles(
            $input->getOption(Option::GLOBAL_CONFIG_FILE),
            $this->configFileResolver->getUserFilePath()
        );
    }

    /**
     * Try to load and merge a configuration file. If the ARGV version is defined then load than, errorring if it can't.
     * Otherwise, fallback to the regular file.
     *
     * @param $argvFileName
     * @param $fallbackFileName
     * @throws ConfigurationException
     */
    private function tryConfigLoadFromFiles($argvFileName, $fallbackFileName)
    {
        if ($argvFileName !== null) {
            if (!file_exists($argvFileName)) {
                throw new \InvalidArgumentException(
                    sprintf('The config file at %s doesn\'t exist', $argvFileName)
                );
            }

            if (!is_readable($argvFileName)) {
                throw new \InvalidArgumentException(
                    sprintf('The config file at %s cannot be read', $argvFileName)
                );
            }

            $this->config->merge((new FileLoader($argvFileName))->asConfig());
        } else {
            if (file_exists($fallbackFileName) && is_readable($fallbackFileName)) {
                $this->getLogger()->info(sprintf('Loading config from %s', $fallbackFileName));
                $this->config->merge((new FileLoader($fallbackFileName))->asConfig());
            } else {
                $this->getLogger()->info(sprintf('Did not load config from %s - did not exist', $fallbackFileName));
            }
        }
    }
}
