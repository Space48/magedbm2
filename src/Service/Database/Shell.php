<?php

namespace Meanbee\Magedbm2\Service\Database;

use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Helper\TablePatternExpander;
use Meanbee\Magedbm2\Exception\ConfigurationException;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Exception\ServiceException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

class Shell implements DatabaseInterface
{
    /**
     * This is the amount of time that a process will be allowed to execute for.
     */
    const PROCESS_TIMEOUT_SECONDS = 3600;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Application\ConfigInterface
     */
    private $config;

    /**
     * @var TablePatternExpander
     */
    private $tablePatternExpander;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Application $app, Application\ConfigInterface $config = null)
    {
        $this->app = $app;
        $this->config = $config;
        $this->tablePatternExpander = new TablePatternExpander();
        $this->logger = new NullLogger();
    }

    /**
     * Import the given backup file into the database.
     *
     * @param string $file Path to a file to import.
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws ServiceException
     */
    public function import($file)
    {
        $command = array_merge(
            ['gunzip -c ' . $file . ' | mysql'],
            $this->getCredentialOptions(),
            [$this->config->getDatabaseCredentials()->getName()]
        );

        $process = $this->createProcess($command);

        $process->start();
        $exitCode = $process->wait();

        if ($exitCode !== 0) {
            throw new ServiceException("There was an error exporting the database: " . $process->getErrorOutput());
        }
    }

    /**
     * Dump the database into a backup file.
     *
     * @param string $identifier An identifier for the dump file.
     * @param string $strip_tables_patterns List of table patterns to dump with no data.
     *
     * @return string Path to the database dump.
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function dump($identifier, $strip_tables_patterns = '')
    {
        $strip_tables = implode(' ', $this->getStripTables($strip_tables_patterns));

        $fileNamePrefix = $identifier . '-' . date('Y-m-d_His');

        $structureOutputFile = $fileNamePrefix . '-structure.sql';
        $dataOutputFile = $fileNamePrefix . '-data.sql';
        $compressedFinalFile = $fileNamePrefix . '.sql.gz';

        $databaseName = $this->config->getDatabaseCredentials()->getName();

        /** @var Process[] $commands */
        $commands = [];

        if (trim($strip_tables) !== '') {
            // Create the structure-only dump for tables that we don't want the data from.
            $commands[] = $this->createDumpProcess([
                '--no-data',
                '--add-drop-table',
                // @TODO Do we need to add --skip-triggers here?
                // @TODO Do we need to add --routines here?
                $databaseName,
                $strip_tables,
                "> $structureOutputFile"
            ]);
        } else {
            // Empty structure file to make the rest of the code more consistent.
            file_put_contents($structureOutputFile, '');
        }

        $dataDumpOptions = array_map(function ($table) use ($databaseName) {
            return sprintf('--ignore-table=%s', escapeshellarg($databaseName . '.' . $table));
        }, explode(' ', $strip_tables));

        $commands[] = $this->createDumpProcess(array_merge($dataDumpOptions, [
            '--add-drop-table',
            // @TODO Do we need to add --skip-triggers here?
            $databaseName,
            "> $dataOutputFile"
        ]));

        $dumpHeader = $this->getDumpHeader();

        $this->logger->info('Starting structure and data dump commands.');

        // Kick the commands off
        array_walk($commands, function (Process $command) {
            $command->start();
        });

        // Wait for all commands to finish before carrying on
        array_walk($commands, function (Process $command) use ($commands) {
            $exitCode = $command->wait();

            if ($exitCode !== 0) {
                $this->logger->alert('There was an error in one of the commands -- stopping others.');

                array_walk($commands, function (Process $command) {
                    $command->stop();
                });
            }

            if ($exitCode !== 0) {
                throw new ServiceException("There was an error exporting the database: " . $command->getErrorOutput());
            }
        });

        $this->logger->info('Starting structure and data dump finished.');

        // Once the exports have finished then start the compression.
        $compressCommand = $this->createProcess(
            sprintf(
                'echo %s | cat - %s %s | gzip -9 --force > %s',
                escapeshellarg($dumpHeader),
                $structureOutputFile,
                $dataOutputFile,
                $compressedFinalFile
            )
        );

        $this->logger->info('Starting compress command.');

        $compressCommand->start();
        $compressCommand->wait();

        $this->logger->info('Compress command finished.');

        $this->logger->info('Cleaning up data and structure files.');

        unlink($dataOutputFile);
        unlink($structureOutputFile);

        return $compressedFinalFile;
    }

    /**
     * @param $command
     * @return Process
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function createProcess($command)
    {
        $commandString = is_array($command) ? implode(' ', $command) : $command;

        $this->logger->debug($commandString);

        return new Process($commandString, null, [], null, self::PROCESS_TIMEOUT_SECONDS);
    }

    /**
     * @param $command
     * @return Process
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function createDumpProcess($command)
    {
        $command = (is_array($command)) ? $command : [$command];

        return $this->createProcess(
            array_merge(['mysqldump'], $this->getCredentialOptions(), $command)
        );
    }

    /**
     *
     */
    private function getCredentialOptions()
    {
        $map = [
            'host' => $this->config->getDatabaseCredentials()->getHost(),
            'user' => $this->config->getDatabaseCredentials()->getUsername(),
            'password' => $this->config->getDatabaseCredentials()->getPassword(),
            'port' => $this->config->getDatabaseCredentials()->getPort()
        ];

        $args = [];

        foreach ($map as $key => $value) {
            if ($value) {
                $args[] = sprintf('--%s=%s', $key, $value);
            }
        }

        return $args;
    }

    /**
     * Check that the database credentials are correct.
     *
     * @inheritdoc
     */
    public function validateConfiguration(): bool
    {
        $this->logger->debug('Validating database credentials..');

        try {
            $this->getPdo()->exec('SELECT 1');
        } catch (\PDOException $e) {
            $this->logger->emergency('Unable to validate database credentials.');
            throw new ConfigurationException(implode(' ', $this->getPdo()->errorInfo()));
        }

        $this->logger->info('Validated database credentials!');

        return true;
    }

    /**
     * @return array
     */
    private function getAllTables(): array
    {
        $result = $this->getPdo()->query('SHOW TABLES');
        return $result->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return \PDO
     */
    private function getPdo()
    {
        $creds = $this->config->getDatabaseCredentials();

        return new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s;port=%s',
                $creds->getName(),
                $creds->getHost(),
                $creds->getPort()
            ),
            $creds->getUsername(),
            $creds->getPassword()
        );
    }

    /**
     * @param string $strip_tables_patterns
     * @return array
     */
    private function getStripTables(string $strip_tables_patterns)
    {
        return $this->tablePatternExpander->expand(explode(' ', $strip_tables_patterns), $this->getAllTables());
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

    /**
     * String to be placed at the top of a dump.
     *
     * @return string
     */
    private function getDumpHeader(): string
    {
        $dumpHeader = sprintf(
            '-- Generator: %s (%s) at %s on %s by %s\n--',
            Application::APP_NAME,
            Application::APP_VERSION,
            date('c'),
            gethostname(),
            get_current_user()
        );
        return $dumpHeader;
    }
}
