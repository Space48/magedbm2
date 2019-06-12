<?php

namespace Meanbee\Magedbm2\Service\Storage;

use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\StorageInterface;

/**
 * A storage adapter that just uses the temp directory on the machine.
 */
class Local implements StorageInterface
{
    /**
     * @var bool
     */
    private $tmpDir;

    /**
     * @var
     */
    private $purpose;

    /**
     * Local constructor.
     */
    public function __construct()
    {
        $this->tmpDir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'magedbm2']);

        $this->ensureDirectoryExists($this->tmpDir);
    }

    /**
     * @inheritdoc
     */
    public function validateConfiguration(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function listProjects()
    {
        $projects = [];
        $projectDirs = scandir($this->tmpDir, SCANDIR_SORT_NONE);

        foreach ($projectDirs as $projectDir) {
            if (is_dir($projectDir)) {
                $projects[] = $projectDir;
            }
        }

        return $projects;
    }

    /**
     * List backup files available in the given project.
     *
     * @param string $project
     *
     * @return File[]
     */
    public function listFiles($project)
    {
        $files = [];

        $projectFiles = scandir($this->getProjectDir($project), SCANDIR_SORT_NONE);

        foreach ($projectFiles as $projectFile) {
            $fullProjectFile = implode(DIRECTORY_SEPARATOR, [$this->getProjectDir($project), $projectFile]);

            if (is_file($fullProjectFile)) {
                $fileObj = new File();

                $fileObj->project = $project;
                $fileObj->name = $projectFile;
                $fileObj->size = filesize($fullProjectFile);
                $fileObj->last_modified = (new \DateTime())->setTimestamp(filemtime($fullProjectFile));

                $files[] = $fileObj;
            }
        }

        return $files;
    }

    /**
     * Get the name of the latest backup file in the given project.
     *
     * @param string $project
     *
     * @return string Backup file name.
     */
    public function getLatestFile($project)
    {
        $newestFileName = null;
        $newestDate = null;

        foreach ($this->listFiles($project) as $projectFile) {
            if (null === $newestDate || $projectFile->last_modified > $newestDate) {
                $newestFileName = $projectFile->name;
                $newestDate = $projectFile->last_modified;
            }
        }

        return $newestFileName;
    }

    /**
     * Upload the given backup file to the given project.
     *
     * @param string $project
     * @param string $file
     *
     * @return string Uploaded file name.
     */
    public function upload($project, $file)
    {
        $destinationFile = $this->getFileInProjectDir($project, $file);

        rename($file, $destinationFile);

        return $destinationFile;
    }

    /**
     * Download the given backup file from the given project.
     *
     * @param string $project
     * @param string $file
     *
     * @return string Path to the downloaded file
     */
    public function download($project, $file)
    {
        $downloadedFile = tempnam($this->tmpDir, 'downloaded');

        copy($this->getFileInProjectDir($project, $file), $downloadedFile);

        return $downloadedFile;
    }

    /**
     * Delete the given backup file from the given project.
     *
     * @param string $project
     * @param string $file
     *
     * @return void
     */
    public function delete($project, $file)
    {
        unlink($this->getFileInProjectDir($project, $file));
    }

    /**
     * Delete old backup files for the given project, keeping only the latest few.
     *
     * @param string $project
     * @param int $keep The number of latest backup files to retain.
     *
     * @return void
     */
    public function clean($project, $keep = 5)
    {
        // TODO: Implement clean() method.
    }

    /**
     * @param $project
     * @return string
     */
    private function getProjectDir($project)
    {
        $dir = implode(DIRECTORY_SEPARATOR, [$this->tmpDir, $project]);
        $this->ensureDirectoryExists($dir);
        return $dir;
    }

    /**
     * @param $project
     * @param $file
     * @return string
     */
    private function getFileInProjectDir($project, $file)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getProjectDir($project), $file]);
    }

    /**
     * @param $dir
     */
    private function ensureDirectoryExists($dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
    }

    /**
     * Define the purpose of this instantiation.
     *
     * @param $purpose
     * @return mixed
     */
    public function setPurpose($purpose)
    {
        $this->purpose = $purpose;
    }
}