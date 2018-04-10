<?php

namespace Meanbee\Magedbm2\Application\ConfigLoader;

use Meanbee\Magedbm2\Application\Config;
use Meanbee\Magedbm2\Application\ConfigLoaderInterface;
use Meanbee\Magedbm2\Exception\ConfigurationException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class FileLoader implements ConfigLoaderInterface
{
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return Config
     * @throws ConfigurationException
     */
    public function asConfig()
    {
        if (!file_exists($this->filePath)) {
            throw new ConfigurationException(sprintf(
                'The configuration file at %s does not exist',
                $this->filePath
            ));
        }

        try {
            $values = Yaml::parseFile($this->filePath);

            return new Config($values);
        } catch (ParseException $exception) {
            throw new ConfigurationException(sprintf(
                'The configuration file at %s does not contain valid YAML',
                $this->filePath
            ));
        }
    }
}
