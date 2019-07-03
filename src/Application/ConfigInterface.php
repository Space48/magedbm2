<?php

namespace Meanbee\Magedbm2\Application;

use Meanbee\Magedbm2\Application\Config\DatabaseCredentials;
use Meanbee\Magedbm2\Application\Config\TableGroup;

interface ConfigInterface
{

    /**
     * Get a config option value.
     *
     * @param string $option
     * @param bool $graceful Whether or not throw an exception if a value is not found.
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function get($option, $graceful = true);

    /**
     * Get all config options in an array.
     *
     * @return array
     */
    public function all();
    
    /**
     * Get the defined table groups.
     *
     * @return TableGroup[]
     */
    public function getTableGroups();

    /**
     *  Get the defined config of selected storage adapter.
     * @return array
     */
    public function getStorageAdapter();

    /**
     * @return DatabaseCredentials
     */
    public function getDatabaseCredentials();

    /**
     * Merge another configuration into this one.
     *
     * @param ConfigInterface $config
     * @return ConfigInterface
     */
    public function merge(ConfigInterface $config);
}
