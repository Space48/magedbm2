<?php

namespace Meanbee\Magedbm2\Service\Database;

use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Service\ServiceException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\StreamOutput;

class Magerun implements DatabaseInterface
{

    /** @var Application */
    protected $app;

    /** @var Application\ConfigInterface */
    protected $config;

    /** @var  \N98\Magento\Application */
    protected $magerun;

    public function __construct(
        Application $app,
        Application\ConfigInterface $config = null,
        \N98\Magento\Application $magerun = null
    ) {
        $this->app = $app;
        $this->config = $config;
        $this->magerun = $magerun;

        $this->addInputOptions($app);
    }

    /**
     * @inheritdoc
     */
    public function import($file)
    {
        $command = $this->getMagerunCommand("db:import");
        $params = [
            "filename" => $file,
        ];

        if (preg_match('/\.gz$/', $file)) {
            $params["--compression"] = "gzip";
        }

        $input = new ArrayInput($params);
        $output = new StreamOutput(fopen('php://memory', 'w', false));
        $exception = null;

        try {
            $command->run($input, $output);
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }

        rewind($output->getStream());
        $command_output = stream_get_contents($output->getStream());

        // Check the output for errors, since Magerun does not return a useful status
        if ($exception || strpos($command_output, "<error>") !== false) {
            $message = ["magerun2 db:import failed:"];

            if ($exception) {
                $message[] = "Exception:";
                $message[] = $exception;
            }

            if ($command_output) {
                $message[] = "Command output:";
                $message[] = $command_output;
            }

            throw new ServiceException(implode(PHP_EOL, $message));
        }
    }

    /**
     * @inheritdoc
     */
    public function dump($identifier, $strip_tables = '')
    {
        $command = $this->getMagerunCommand("db:dump");
        $file = $this->getBackupFilePath($identifier);
        $params = [
            "filename"       => $file,
            "--compression"  => "gzip",
            "--add-routines" => true,
            "--force"        => true,
        ];

        if ($strip_tables) {
            $params["--strip"] = $strip_tables;
        }

        $input = new ArrayInput($params);
        $output = new StreamOutput(fopen('php://memory', 'w', false));
        $exception = null;

        try {
            $command->run($input, $output);
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }

        rewind($output->getStream());
        $command_output = stream_get_contents($output->getStream());

        // Check the output for errors, since Magerun does not return a useful status
        if ($exception || strpos($command_output, "<error>") !== false) {
            $message = ["magerun2 db:dump failed:"];

            if ($exception) {
                $message[] = "Exception:";
                $message[] = $exception;
            }

            if ($command_output) {
                $message[] = "Command output:";
                $message[] = $command_output;
            }

            throw new ServiceException(implode(PHP_EOL, $message));
        }

        return $file;
    }

    /**
     * Get the configuration model.
     *
     * @return Application\ConfigInterface
     */
    protected function getConfig()
    {
        if (!$this->config instanceof Application\ConfigInterface) {
            $this->config = $this->app->getConfig();
        }

        return $this->config;
    }

    /**
     * Get Magerun client.
     *
     * @return \N98\Magento\Application
     */
    protected function getMagerun()
    {
        if (!$this->magerun instanceof \N98\Magento\Application) {
            $this->magerun = new \N98\Magento\Application($this->app->getAutoloader());
            $this->magerun->init();
        }

        return $this->magerun;
    }

    /**
     * Get a command from Magerun.
     *
     * @param string $name
     *
     * @return \Symfony\Component\Console\Command\Command
     * @throws ServiceException
     */
    protected function getMagerunCommand($name)
    {
        $command = $this->getMagerun()->get($name);

        if (!$command) {
            throw new ServiceException(sprintf(
                "Unable to find magerun2 %s command. Dependencies may be missing.",
                $name
            ));
        }

        return $command;
    }

    /**
     * Generate a backup file name using a given identifier and return the path to it.
     *
     * @param string $identifier
     *
     * @return string
     */
    protected function getBackupFilePath($identifier)
    {
        $dir = $this->getConfig()->getTmpDir();
        if ($dir && substr($dir, -1) !== DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        $timestamp = date("Y-m-d_His");

        return sprintf("%s%s-%s.sql.gz", $dir, $identifier, $timestamp);
    }

    /**
     * Add service input options to the application.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function addInputOptions(Application $app)
    {
        $definition = $app->getDefinition();

        $definition->addOption(new InputOption(
            "root-dir",
            null,
            InputOption::VALUE_REQUIRED,
            "Magento 2 root directory. Disables automatic detection."
        ));
    }
}
