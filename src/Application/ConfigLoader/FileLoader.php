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

            if (is_array($values) && !$this->validateYamlVariables($values)) {
                $this->configurationError();
            }

            if (is_array($values)) {
                $values = $this->formatConfigVariableNames($values);
            }

            return new Config($values);
        } catch (ParseException $exception) {
            $this->configurationError();
        }
    }

    // yaml doesn't like variable names with hyphens (https://github.com/Space48/magedbm2/issues/21)
    private function validateYamlVariables(array $variables)
    {
        $isValidYaml = true;
        foreach ($variables as $variableName => $variableValue) {
            if (strpos($variableName, '-') !== false) {
                $isValidYaml = false;
                break;
            }
        }
        return $isValidYaml;
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
