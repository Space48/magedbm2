<?php

namespace Meanbee\Magedbm2\Application\ConfigLoader;

use Meanbee\Magedbm2\Application\Config;
use Meanbee\Magedbm2\Application\Config\Option;
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

            if (is_array($values)) {
                $values = $this->formatConfigVariableNames($values);
            }

            return new Config($values);
        } catch (ParseException $exception) {
            $this->configurationError();
        }
    }

    private function formatConfigVariableNames(array $variables)
    {
        foreach ($variables as $variableName => $variableValue) {
            $configOption = Option::mapYamlOptionToConfigOption($variableName);
            if ($configOption && $configOption !== $variableName) {
                $variables[$configOption] = $variableValue;
                unset($variables[$variableName]);
            }
        }
        return $variables;
    }

    // the following line throws a void return not allowed for php <7.0, we do not support php 7.0
    private function configurationError(): void //phpcs:ignore
    {
        throw new ConfigurationException(sprintf(
            'The configuration file at %s does not contain valid YAML',
            $this->filePath
        ));
    }
}
