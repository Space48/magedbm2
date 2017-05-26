<?php

namespace Meanbee\Magedbm2\Service\Storage;

use Aws\S3\S3Client;
use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Service\ServiceException;
use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\StorageInterface;
use Symfony\Component\Console\Input\InputOption;

class S3 implements StorageInterface
{
    /** @var Application */
    protected $app;

    /** @var Application\ConfigInterface */
    protected $config;

    /** @var S3Client */
    protected $client;

    /**
     * Default S3 client parameters.
     *
     * @var array
     */
    protected $default_params = [
        "version" => "latest",
    ];

    public function __construct(Application $app, Application\ConfigInterface $config = null, S3Client $client = null)
    {
        $this->app = $app;
        $this->config = $config;

        if ($client) {
            $this->client = $client;
        }

        $this->addInputOptions($app);
    }

    /**
     * @inheritdoc
     */
    public function listProjects()
    {
        $bucket = $this->getConfig()->get("bucket");

        try {
            $objects = $this->getClient()->getIterator("ListObjects", [
                "Bucket" => $bucket,
            ]);

            $projects = [];
            foreach ($objects as $object) {
                if ($project = strstr($object["Key"], "/", true)) {
                    $projects[$project] = 1;
                }
            }
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        return array_keys($projects);
    }

    /**
     * @inheritdoc
     */
    public function listFiles($project)
    {
        $bucket = $this->getConfig()->get("bucket");
        $prefix = $this->getFileKey($project, "");

        try {
            $objects = $this->getClient()->getIterator("ListObjects", [
                "Bucket" => $bucket,
                "Prefix" => $prefix,
            ]);

            $files = [];
            foreach ($objects as $object) {
                $file = new File();

                $file->name = substr(strrchr($object["Key"], "/"), 1);
                $file->project = $project;
                $file->size = $object["Size"];
                $file->last_modified = $object["LastModified"];

                $files[] = $file;
            }
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        return $files;
    }

    /**
     * @inheritdoc
     *
     * @return string|null
     */
    public function getLatestFile($project)
    {
        /** @var File $latest */
        $latest = null;

        foreach ($this->listFiles($project) as $file) {
            if (!$latest || $file->last_modified > $latest->last_modified) {
                $latest = $file;
            }
        }

        return ($latest) ? $latest->name : null;
    }

    /**
     * @inheritdoc
     */
    public function upload($project, $file)
    {
        $key = $this->getFileKey($project, basename($file));
        $bucket = $this->getConfig()->get("bucket");

        try {
            $result = $this->getClient()->putObject([
                "Bucket"     => $bucket,
                "Key"        => $key,
                "SourceFile" => $file,
            ]);
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        return $result["ObjectURL"];
    }

    /**
     * @inheritdoc
     */
    public function download($project, $file)
    {
        $key = $this->getFileKey($project, $file);
        $bucket = $this->getConfig()->get("bucket");
        $local_file = implode(DIRECTORY_SEPARATOR, [
            $this->getConfig()->getTmpDir(),
            $file
        ]);

        try {
            $this->getClient()->getObject([
                "Bucket" => $bucket,
                "Key"    => $key,
                "SaveAs" => $local_file,
            ]);
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        return $local_file;
    }

    /**
     * @inheritdoc
     */
    public function delete($project, $file)
    {
        $key = $this->getFileKey($project, $file);
        $bucket = $this->getConfig()->get("bucket");

        try {
            $this->getClient()->deleteObject([
                "Bucket" => $bucket,
                "Key"    => $key,
            ]);
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function clean($project, $keep = 5)
    {
        $bucket = $this->getConfig()->get("bucket");
        $files = $this->listFiles($project);

        // Sort files by last_modified ascending
        usort($files, function (File $a, File $b) {
            if ($a->last_modified == $b->last_modified) {
                return 0;
            }

            return ($a->last_modified < $b->last_modified) ? -1 : 1;
        });

        // Slice the latest 5 files off and convert to array of keys
        $objects = array_map(function (File $file) {
            return [
                "Key" => ($file->name) ? $this->getFileKey($file->project, $file->name) : null,
            ];
        }, array_slice($files, 0, -1 * $keep));

        // Filter out empty keys
        $objects = array_filter($objects, function ($object) {
            return $object["Key"] != null;
        });

        // Delete remaining objects
        if (!empty($objects)) {
            try {
                $this->getClient()->deleteObjects([
                    "Bucket" => $bucket,
                    "Delete" => [
                        "Objects" => $objects,
                    ],
                ]);
            } catch (\Exception $e) {
                throw new ServiceException($e->getMessage());
            }
        }
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
     * Get the S3 API client.
     *
     * @return S3Client
     */
    protected function getClient()
    {
        if (!$this->client instanceof S3Client) {
            $params = $this->default_params;

            if ($region = $this->getConfig()->get("region")) {
                $params["region"] = $region;
            }

            if (($access_key = $this->getConfig()->get("access-key"))
                && ($secret_key = $this->getConfig()->get("secret-key"))
            ) {
                $params["credentials"] = [
                    "key"    => $access_key,
                    "secret" => $secret_key,
                ];
            }

            $this->client = new S3Client($params);
        }

        return $this->client;
    }

    /**
     * Get the S3 key for the given file in the given project.
     *
     * @param string $project
     * @param string $file
     *
     * @return string
     */
    protected function getFileKey($project, $file)
    {
        return sprintf("%s/%s", $project, $file);
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
            "access-key",
            null,
            InputOption::VALUE_REQUIRED,
            "S3 Access Key ID"
        ));

        $definition->addOption(new InputOption(
            "secret-key",
            null,
            InputOption::VALUE_REQUIRED,
            "S3 Secret Access Key"
        ));

        $definition->addOption(new InputOption(
            "region",
            null,
            InputOption::VALUE_REQUIRED,
            "S3 region"
        ));

        $definition->addOption(new InputOption(
            "bucket",
            null,
            InputOption::VALUE_REQUIRED,
            "S3 bucket"
        ));
    }
}
