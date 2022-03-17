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
            if (!$this->validateYamlVariables($values)) {
                $this->configurationError();
            }
            $values = $this->formatConfigVariableNames($values);
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
        $yamlToConfigVariableNameMap = Option::getYamlOptionMap();
        foreach ($variables as $variableName => $variableValue) {
            if (array_key_exists($variableName, $yamlToConfigVariableNameMap)) {
                $variables[$yamlToConfigVariableNameMap[$variableName]] = $variableValue;
                unset($variables[$variableName]);
            }
        }
        return $variables;
    }

    private function configurationError(): void
    {
        throw new ConfigurationException(sprintf(
            'The configuration file at %s does not contain valid YAML',
            $this->filePath
        ));
    }
}
