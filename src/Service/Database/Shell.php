<?php

namespace Meanbee\Magedbm2\Service\Database;

use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Helper\TablePatternExpander;
use Meanbee\Magedbm2\Exception\ConfigurationException;
use Meanbee\Magedbm2\Service\DatabaseInterface;
use Meanbee\Magedbm2\Exception\ServiceException;
use Meanbee\Magedbm2\Shell\Command\Cat;
use Meanbee\Magedbm2\Shell\Command\EchoPrint;
use Meanbee\Magedbm2\Shell\Command\Gunzip;
use Meanbee\Magedbm2\Shell\Command\Gzip;
use Meanbee\Magedbm2\Shell\Command\Mysql;
use Meanbee\Magedbm2\Shell\Command\Mysqldump;
use Meanbee\Magedbm2\Shell\Command\Sed;
use Meanbee\Magedbm2\Shell\CommandInterface;
use Meanbee\Magedbm2\Shell\Pipe;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    public function __construct(Application $app, Application\ConfigInterface $config)
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
        $process = (new Pipe())
            ->command(
                (new Gunzip())
                    ->argument('-c')
                    ->argument($file)
            )->command(
                (new Mysql())
                    ->arguments($this->getCredentialOptions())
                    ->argument($this->config->getDatabaseCredentials()->getName())
            )->toProcess();

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

        $structureOutputFile = $this->getTempFile($fileNamePrefix . '-structure.sql');
        $dataOutputFile = $this->getTempFile($fileNamePrefix . '-data.sql');
        $compressedFinalFile = $this->getTempFile($fileNamePrefix . '.sql.gz');

        $databaseName = $this->config->getDatabaseCredentials()->getName();

        /** @var Process[] $commands */
        $commands = [];

        if (trim($strip_tables) !== '') {
            // Create the structure-only dump for tables that we don't want the data from.
            $commands[] = $this->createDumpProcess()
                ->argument('--no-data')
                ->argument('--add-drop-table')
                ->argument($databaseName)
                ->argument($strip_tables)
                ->output($structureOutputFile);
        } else {
            // Empty structure file to make the rest of the code more consistent.
            file_put_contents($structureOutputFile, '');
        }

        $dataDumpOptions = array_map(function ($table) use ($databaseName) {
            return sprintf('--ignore-table=%s', escapeshellarg($databaseName . '.' . $table));
        }, explode(' ', $strip_tables));

        $commands[] = $this->createDumpProcess()
            ->arguments($dataDumpOptions)
            ->argument('--add-drop-table')
            ->argument($databaseName)
            ->output($dataOutputFile);

        $commandProcesses = array_map(function (CommandInterface $command) {
            $this->logger->debug($command->toString());
            return $command->toProcess();
        }, $commands);

        $dumpHeader = $this->getDumpHeader();

        $this->logger->info('Starting structure and data dump commands.');

        // Kick the commands off
        $this->startCommands($commandProcesses);

        // Wait for all commands to finish before carrying on
        $this->waitToFinish($commandProcesses);

        $this->logger->info('Starting structure and data dump finished.');

        // Once the exports have finished then start the compression.
        $compressCommand = (new Pipe())
            ->command(
                (new EchoPrint(escapeshellarg($dumpHeader)))
            )->command(
                (new Cat())
                    ->argument('-')
                    ->argument($structureOutputFile)
                    ->argument($dataOutputFile)
            )->command(
                (new Sed())
                    ->argument("-e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/'")
            )->command(
                (new Gzip())
                    ->argument('-9')
                    ->argument('--force')
                    ->output($compressedFinalFile)
            );

        $compressCommandProcess = $compressCommand->toProcess();

        $this->logger->info('Starting compress command');
        $this->logger->debug($compressCommand->toString());

        $compressCommandProcess->start();
        $compressCommandProcess->wait();

        $this->logger->info('Compress command finished.');

        $this->logger->info('Cleaning up data and structure files.');

        unlink($dataOutputFile);
        unlink($structureOutputFile);

        return $compressedFinalFile;
    }

    /**
     * @return Mysqldump
     */
    private function createDumpProcess()
    {
        return (new Mysqldump())
            ->arguments($this->getCredentialOptions());
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
        return $this->config->getDatabaseCredentials()->createPDO();
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

    /**
     * Kick off an array of commands.
     *
     * @param Process[] $commands
     */
    private function startCommands($commands)
    {
        array_walk($commands, function (Process $command) {
            $command->start();
        });
    }

    /**
     *
     *
     * @param Process[] $commands
     */
    private function waitToFinish($commands)
    {
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
    }

    /**
     * Return the full file path to a temporary file.
     *
     * @param $filename
     * @return string
     */
    private function getTempFile($filename)
    {
        return $this->config->get(Application\Config\Option::TEMPORARY_DIR) . DIRECTORY_SEPARATOR . $filename;
    }
}
