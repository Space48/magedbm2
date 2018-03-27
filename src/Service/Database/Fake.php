<?php

namespace Meanbee\Magedbm2\Service\Database;

use Meanbee\Magedbm2\Service\DatabaseInterface;
use Psr\Log\LoggerInterface;
use VirtualFileSystem\FileSystem;

class Fake implements DatabaseInterface
{
    const DUMP_FILE_LOCATION = '/fake-dump.sql.gz';

    /**
     * @var FileSystem
     */
    private $fs;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(FileSystem $fs = null)
    {
        $this->fs = $fs ?? new FileSystem();
    }

    /**
     * Evaluate whether the configuration for the database adapter is correct.
     */
    public function validateConfiguration(): bool
    {
        return true;
    }

    /**
     * Import the given backup file into the database.
     *
     * @param string $file Path to a file to import.
     *
     * @return void
     */
    public function import($file)
    {
    }

    /**
     * Dump the database into a backup file.
     *
     * @param string $identifier An identifier for the dump file.
     * @param string $strip_tables List of tables to dump with no data.
     *
     * @return string Path to the database dump.
     */
    public function dump($identifier, $strip_tables = '')
    {
        $file = $this->fs->path(self::DUMP_FILE_LOCATION);

        file_put_contents($file, date('r'));

        return $file;
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
