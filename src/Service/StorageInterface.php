<?php

namespace Meanbee\Magedbm2\Service;

interface StorageInterface
{
    /**
     * Get the name of the latest backup file in the given project.
     *
     * @param string $project
     *
     * @return string Backup file name.
     */
    public function getLatestFile($project);

    /**
     * Download the given backup file from the given project.
     *
     * @param string $project
     * @param string $file
     *
     * @return string Path to the downloaded file
     */
    public function download($project, $file);
}
