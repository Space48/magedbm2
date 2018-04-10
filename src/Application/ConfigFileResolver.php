<?php

namespace Meanbee\Magedbm2\Application;

class ConfigFileResolver
{
    /**
     * @return string
     */
    public function getDistFilePath()
    {
        return $this->buildPath([__DIR__, '..', '..', 'etc', 'config.yml']);
    }

    /**
     * @return string
     */
    public function getUserFilePath()
    {
        return $this->buildPath([getenv('HOME'), '.magedbm2', 'config.yml']);
    }

    /**
     * @return string
     */
    public function getProjectFilePath()
    {
        return getcwd() . DIRECTORY_SEPARATOR . '.magedbm2.yml';
    }

    /**
     * @param array $pathParts
     * @return string
     */
    private function buildPath($pathParts)
    {
        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }
}
