<?php

namespace Meanbee\Magedbm2\Application\Config;

use Meanbee\LibMageConf\RootDiscovery;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Combined implements ConfigInterface
{
    const DIST_CONFIG_FILE    = __DIR__ . "/../../../etc/config.yml";
    
    const DEFAULT_CONFIG_FILE = "~/.magedbm2/config.yml";

    const KEY_TABLE_GROUPS = 'table-groups';

    const KEY_DB_HOST      = 'db-host';
    const KEY_DB_NAME      = 'db-name';
    const KEY_DB_USER      = 'db-user';
    const KEY_DB_PASS      = 'db-pass';
    const KEY_DB_PORT      = 'db-port';

    protected $data = [];

    protected $loaded = false;

    /** @var Application */
    protected $app;

    /** @var InputInterface */
    protected $input;

    /** @var Yaml */
    protected $yaml;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DatabaseCredentials */
    private $databaseCredentials;

    public function __construct(Application $app, InputInterface $input, Yaml $yaml)
    {
        $this->app = $app;
        $this->input = $input;
        $this->yaml = $yaml;
        $this->logger = new NullLogger();

        $this->addInputOptions($app);
    }

    /**
     * Load the configuration
     *
     * @return $this
     */
    protected function load()
    {
        $initialData = $this->data ?? [];

        if (!$this->loaded) {
            $this->loadDistConfig();
            $this->loadDefaultConfig();
            $this->loaded = true;
        }

        $this->loadFromFile($this->getConfigFile());
        $this->loadFromInput($this->input);

        $finalData = $this->data ?? [];

        if ($initialData != $finalData) {
            $dataDiff = @array_diff($finalData, $initialData);
            $this->logger->debug("Configuration changed:\n\n" . $this->yaml->dump($dataDiff));
        }

        return $this;
    }

    /**
     * Erase all configuration and reset the status.
     *
     * @return $this
     */
    public function reset()
    {
        $this->data = [];
        $this->loaded = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get($option)
    {
        $this->load();

        return $this->data[$option] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getTmpDir()
    {
        $dir = $this->get("tmp_dir");

        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException(sprintf("Unable to create the temporary directory '%s'", $dir));
            }
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RuntimeException(sprintf("Unable to write a temporary backup file to '%s'", $dir));
        }

        return $dir;
    }

    /**
     * @inheritdoc
     */
    public function getWorkingDir()
    {
        return getcwd();
    }

    /**
     * @inheritdoc
     */
    public function getRootDir()
    {
        try {
            return $this->input->getOption("root-dir");
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function getDatabaseCredentials()
    {
        if ($this->databaseCredentials === null) {
            $this->load();

            $rootDiscovery = new RootDiscovery($this->getRootDir() ?? $this->getWorkingDir());
            $configReader = $rootDiscovery->getConfigReader();

            if (\Meanbee\LibMageConf\MagentoType::UNKNOWN !== $rootDiscovery->getInstallationType()) {
                $this->databaseCredentials = new DatabaseCredentials(
                    $configReader->getDatabaseName(),
                    $configReader->getDatabaseUsername(),
                    $configReader->getDatabasePassword(),
                    $configReader->getDatabaseHost(),
                    $configReader->getDatabasePort()
                );
            } else {
                $this->logger->warning(
                    'Unable to find a Magento installation, using database credentials from configuration.'
                );

                $this->databaseCredentials = new DatabaseCredentials(
                    $this->get(self::KEY_DB_NAME) ?? '',
                    $this->get(self::KEY_DB_USER) ?? '',
                    $this->get(self::KEY_DB_PASS) ?? '',
                    $this->get(self::KEY_DB_HOST) ?? 'localhost',
                    $this->get(self::KEY_DB_PORT) ?? '3306'
                );
            }
        }

        return $this->databaseCredentials;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function getTableGroups(): array
    {
        $tableGroupsConfig = $this->get(static::KEY_TABLE_GROUPS);
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
    
    /**
     * @inheritdoc
     */
    public function getConfigFile()
    {
        try {
            $configFile = $this->input->getOption('config');

            if ($configFile) {
                return $configFile;
            }
        } catch (InvalidArgumentException $e) {
            return $this->getDefaultConfigFile();
        }

        return $this->getDefaultConfigFile();
    }

    /**
     * @inheritdoc
     */
    public function set($option, $value)
    {
        $this->load();

        $this->data[$option] = $value;
    }

    /**
     * Get the path to the default configuration file.
     *
     * @return string
     */
    public function getDefaultConfigFile()
    {
        $config_path = array_filter(array_map(function ($section) {
            switch ($section) {
                case "~":
                    return getenv("HOME");
                default:
                    return $section;
            }
        }, explode("/", self::DEFAULT_CONFIG_FILE)));

        return implode(DIRECTORY_SEPARATOR, $config_path);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function getServicePreference($service)
    {
        switch ($service) {
            case 'storage':
                return 's3';
            case 'database':
                return 'shell';
            case 'filesystem':
                return 'simple';
            default:
                throw new \InvalidArgumentException(sprintf('An unknown service (%s) was requested', $service));
        }
    }

    /**
     * Load base configuration shipped with the distribution.
     */
    protected function loadDistConfig()
    {
        $this->loadFromFile(static::DIST_CONFIG_FILE);
    }
    
    /**
     * Load default configuration option values.
     */
    protected function loadDefaultConfig()
    {
        $this->data = array_merge($this->data, [
            "tmp_dir" => sys_get_temp_dir() . DIRECTORY_SEPARATOR . "magedbm2",
        ]);
    }

    /**
     * Load configuration options from the given config file.
     *
     * @param string $file
     */
    protected function loadFromFile($file)
    {
        $this->logger->debug(sprintf('Loading config from %s', $file));

        if (is_readable($file)) {
            try {
                $config = $this->yaml->parse(file_get_contents($file));
            } catch (ParseException $e) {
                throw new RuntimeException(sprintf(
                    "Unable to read the configuration file '%s': %s",
                    $file,
                    $e->getMessage()
                ));
            }

            if (is_array($config)) {
                $this->data = array_merge($this->data, $config);
            }
        }
    }

    /**
     * Load configuration options from the console input.
     *
     * @param InputInterface $input
     */
    protected function loadFromInput(InputInterface $input)
    {
        $this->logger->debug('Loading config from input interface');

        $this->data = array_merge(
            $this->data,
            array_filter($input->getOptions(), function ($value) {
                return $value !== null;
            })
        );
    }

    /**
     * Add the global config input options to the application.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function addInputOptions($app)
    {
        $definition = $app->getDefinition();

        $definition
            ->addOption(new InputOption(
                "config",
                null,
                InputOption::VALUE_REQUIRED,
                "Configuration file to use",
                $this->getDefaultConfigFile()
            ));

        $definition
            ->addOption(new InputOption(
                self::KEY_DB_HOST,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database host'
            ));

        $definition
            ->addOption(new InputOption(
                self::KEY_DB_PORT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database port'
            ));

        $definition
            ->addOption(new InputOption(
                self::KEY_DB_USER,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database username'
            ));

        $definition
            ->addOption(new InputOption(
                self::KEY_DB_PASS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database password'
            ));

        $definition
            ->addOption(new InputOption(
                self::KEY_DB_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database name'
            ));


        $definition->addOption(new InputOption(
            "root-dir",
            null,
            InputOption::VALUE_REQUIRED,
            "Magento 2 root directory"
        ));
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
