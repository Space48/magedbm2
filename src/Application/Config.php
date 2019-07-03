<?php

namespace Meanbee\Magedbm2\Application;

use Adbar\Dot;
use InvalidArgumentException;
use Meanbee\LibMageConf\RootDiscovery;
use Meanbee\Magedbm2\Application\Config\DatabaseCredentials;
use Meanbee\Magedbm2\Application\Config\Option;
use Meanbee\Magedbm2\Application\Config\TableGroup;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Config implements ConfigInterface, LoggerAwareInterface
{
    /**
     * @var Dot
     */
    private $values;

    /**
     * @var DatabaseCredentials
     */
    private $databaseCredentials;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Config constructor.
     * @param $values
     */
    public function __construct($values = [])
    {
        $this->values = new Dot($values);
        $this->logger = new NullLogger();

        $this->initialiseCalculatedValues();
    }

    /**
     * @param $path
     * @param bool $graceful If false then an exception is thrown if the $path is not found in the config
     * @return mixed|null
     *
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function get($path, $graceful = false)
    {
        if ($this->values->has($path)) {
            return $this->values->get($path);
        }

        if ( ! $graceful) {
            throw new InvalidArgumentException(sprintf('No value set for %s found', $path));
        }

        return null;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->values->all();
    }

    /**
     * @return DatabaseCredentials
     */
    public function getDatabaseCredentials()
    {
        if ($this->databaseCredentials === null) {
            if ( ! $this->get(Option::DB_NAME, true) || ! $this->get(Option::DB_USER, true)) {
                $rootDir = $this->get(Option::ROOT_DIR, true) ?? getcwd();
                $rootDiscovery = new RootDiscovery($rootDir);
                $configReader = $rootDiscovery->getConfigReader();

                if (\Meanbee\LibMageConf\MagentoType::UNKNOWN !== $rootDiscovery->getInstallationType()) {
                    $this->logger->info('Found Magento installation at ' . $rootDiscovery->getRootDirectory());

                    $this->databaseCredentials = new DatabaseCredentials(
                        $configReader->getDatabaseName(),
                        $configReader->getDatabaseUsername(),
                        $configReader->getDatabasePassword(),
                        $configReader->getDatabaseHost(),
                        $configReader->getDatabasePort()
                    );

                    return $this->databaseCredentials;
                } else {
                    $this->logger->warning(
                        'Unable to find a Magento installation, using database credentials from configuration.'
                    );
                }
            }

            $this->databaseCredentials = new DatabaseCredentials(
                $this->get(Option::DB_NAME, true) ?? '',
                $this->get(Option::DB_USER, true) ?? '',
                $this->get(Option::DB_PASS, true) ?? '',
                $this->get(Option::DB_HOST, true) ?? 'localhost',
                $this->get(Option::DB_PORT, true) ?? '3306'
            );
        }

        return $this->databaseCredentials;
    }

    /**
     * @return TableGroup[]
     */
    public function getTableGroups()
    {
        $tableGroupsConfig = $this->get(Option::TABLE_GROUPS);
        $tableGroups = [];

        if ($tableGroupsConfig) {
            foreach ($tableGroupsConfig as $singleTableGroupConfig) {
                $id = $singleTableGroupConfig['id'] ?? null;
                $description = $singleTableGroupConfig['description'] ?? null;
                $tables = $singleTableGroupConfig['tables'] ?? null;

                if ($id === null) {
                    throw new \RuntimeException("Expected table group to have an id");
                }

                if ($description === null) {
                    throw new \RuntimeException("Expected table group to have a description");
                }

                if ($tables === null) {
                    throw new \RuntimeException("Expected table group to have tables");
                }

                $tableGroups[] = new TableGroup($id, $description, $tables);
            }
        }

        return $tableGroups;
    }

    /** Return config of selected storage adapter
     * @return array
     */
    public function getStorageAdapter()
    {
        $storageAdapterConfig = $this->get(Option::STORAGE_ADAPTERS);
        $selectedAdapter = $this->get(Option::SELECTED_STORAGE_ADAPTER);

        if (array_key_exists($selectedAdapter, $storageAdapterConfig)) {
            $config = $storageAdapterConfig[$selectedAdapter];

            //TODO: Perhaps check for certain minimum requirements per storage adapter
            $region = $config['region'] ?? null;
            $accessKey = $config['access-key'] ?? null;
            $secretKey = $config['secret-key'] ?? null;

            if ($region === null) {
                throw new \RuntimeException("Expected storage adapter to have a region");
            }

            if ($accessKey === null) {
                throw new \RuntimeException("Expected storage adapter to have an access-key");
            }

            if ($secretKey === null) {
                throw new \RuntimeException("Expected storage adapter to have a secret-key");
            }

            return $storageAdapterConfig[$selectedAdapter];
        } else {
            throw new \RuntimeException("No configuration found for selected adapter " . $selectedAdapter);
        }
    }

    private function initialiseCalculatedValues()
    {
        if ( ! $this->values->has(Option::TEMPORARY_DIR)) {
            $this->values->set(Option::TEMPORARY_DIR, $this->generateTmpDir());
        }
    }

    /**
     * @return mixed
     * @throws \RuntimeException
     */
    private function generateTmpDir()
    {
        return sys_get_temp_dir();
    }

    /**
     * @param ConfigInterface $config
     * @return $this
     */
    public function merge(ConfigInterface $config)
    {
        $oldData = $this->all();

        $this->values = new Dot($this->doMerge($oldData, $config->all()));
        $this->databaseCredentials = null;

        $newData = $this->all();

        $dataDiff = @array_diff($oldData, $newData);

        if (count($dataDiff)) {
            $this->logger->debug(print_r($dataDiff, true));
        }

        return $this;
    }

    /**
     * Recursively merge a configuration array.
     *
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    private function doMerge($oldValues, $newValues)
    {
        foreach ($newValues as $key => $value) {
            if ( ! isset($oldValues[$key]) || is_scalar($oldValues[$key])) {
                $oldValues[$key] = $value;
            } elseif ($this->arrayDepth($value) === 1) {
                $oldValues[$key] = array_merge($oldValues[$key], $newValues[$key]);
            } else {
                $oldValues[$key] = $this->doMerge($oldValues[$key], $newValues[$key]);
            }
        }

        return $oldValues;
    }

    /**
     * Return the depth of an array.
     *
     * @param $array
     * @return int
     */
    private function arrayDepth($array)
    {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->arrayDepth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
