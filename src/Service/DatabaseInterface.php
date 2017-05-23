<?php

namespace Meanbee\Magedbm2\Service;

interface DatabaseInterface
{

    /**
     * Import the given backup file into the database.
     *
     * @param string $file Path to a file to import.
     *
     * @return void
     */
    public function import($file);

    /**
     * Dump the database into a backup file.
     *
     * @param string $identifier   An identifier for the dump file.
     * @param string $strip_tables List of tables to dump with no data.
     *
     * @return string Path to the database dump.
     */
    public function dump($identifier, $strip_tables = "@development");
}
