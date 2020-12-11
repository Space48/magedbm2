<?php

namespace Meanbee\Magedbm2;

use Composer\Autoload\ClassLoader;
use DI\ContainerBuilder;
use Meanbee\Magedbm2\Application\Config;
use Meanbee\Magedbm2\Application\Config\Option;
use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Application\ConfigLoader\FileLoader;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\FilesystemInterface;
use Meanbee\Magedbm2\Service\StorageInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 * @package Meanbee\Magedbm2
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application extends \Symfony\Component\Console\Application
{

    const APP_NAME = "magedbm2";
    const APP_VERSION = "3.0.1";

    /** @var ClassLoader $autoloader */
    protected $autoloader;

    /** @var Config $config */
    protected $config;

    /** @var array */
    protected $services;

    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     * Application constructor.
     * @param ClassLoader|null $autoloader
     * @throws \Exception
     */
    public function __construct(ClassLoader $autoloader = null)
    {
        parent::__construct(static::APP_NAME, static::APP_VERSION);

        $this->autoloader = $autoloader;

        $builder = new ContainerBuilder();
        $builder->addDefinitions(implode(DIRECTORY_SEPARATOR, [
            __DIR__, '..', 'etc', 'di.php'
        ]));

        $this->container = $builder->build();
        $this->container->set(Application::class, $this);
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
     * @inheritdoc
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->container->set('logger', new ConsoleLogger($output));

        $this->config = $this->container->get('config');
        $this->initCommands();

        return parent::doRun($input, $output);
    }

    /**
     * Initialise the available commands.
     *
     * @return void
     */
    protected function initCommands()
    {
        /** @var Command[] $commands */
        $commands = $this->container->get('command_instances');

        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
