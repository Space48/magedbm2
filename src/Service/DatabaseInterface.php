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
}
