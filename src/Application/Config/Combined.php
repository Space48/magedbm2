<?php

namespace Meanbee\Magedbm2\Application\Config;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Combined implements ConfigInterface
{
    const DEFAULT_CONFIG_FILE = "~/.magedbm2/config.ini";

    protected $data = [];

    protected $loaded = false;

    /** @var Application */
    protected $app;

    /** @var InputInterface */
    protected $input;

    public function __construct(Application $app, InputInterface $input)
    {
        $this->app = $app;
        $this->input = $input;

        $this->addInputOptions($app);
    }

    /**
     * Load the configuration
     *
     * @return $this
     */
    public function load()
    {
        if (!$this->loaded) {
            $this->loadDefaultConfig();
            $this->loadFromFile($this->input->getOption("config") ?: $this->getDefaultConfigFile());
            $this->loadFromInput($this->input);

            $this->loaded = true;
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

        return $this->data[$option];
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
        if (is_readable($file)) {
            if ($config = parse_ini_file($file)) {
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
    }
}
