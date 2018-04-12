<?php

namespace Meanbee\Magedbm2\Application;

interface ConfigLoaderInterface
{
    /**
     * @return Config
     */
    public function asConfig();
}
