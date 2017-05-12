<?php

namespace Meanbee\Magedbm2;

use Composer\Autoload\ClassLoader;

class Application extends \Symfony\Component\Console\Application
{

    const APP_NAME = "magedbm2";
    const APP_VERSION = "1.0.0";

    /** @var ClassLoader $autoloader */
    protected $autoloader;

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
}
