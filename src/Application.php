<?php

namespace Meanbee\Magedbm2;

use Composer\Autoload\ClassLoader;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{

    const APP_NAME = "magedbm2";
    const APP_VERSION = "1.0.0";

    /** @var ClassLoader $autoloader */
    protected $autoloader;

    /** @var ConfigInterface $config */
    protected $config;

    public function __construct(ClassLoader $autoloader = null)
    {
        parent::__construct(static::APP_NAME, static::APP_VERSION);

        $this->autoloader = $autoloader;
    }

    /**
     * Set the autoloader.
     *
     * @param ClassLoader $autoloader
     *
     * @return $this
     */
    public function setAutoloader(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;

        return $this;
    }

    /**
     * Get the autoloader.
     *
     * @return ClassLoader
     */
    public function getAutoloader()
    {
        return $this->autoloader;
    }

    /**
     * Get the application config.
     *
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->init($input);

        return parent::doRun($input, $output);
    }

    /**
     * Initialise the application, including configuration, services and available commands.
     *
     * @param InputInterface  $input
     *
     * return void
     */
    public function init(InputInterface $input)
    {
        $this->initConfig($input);
    }

    /**
     * Initialise the application config.
     *
     * @param InputInterface $input
     *
     * @return void
     */
    protected function initConfig(InputInterface $input)
    {
        $this->config = new Application\Config\Combined($this, $input);
    }
}
