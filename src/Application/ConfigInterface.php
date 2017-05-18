<?php

namespace Meanbee\Magedbm2\Application;

interface ConfigInterface
{

    /**
     * Get a config option value.
     *
     * @param string $option
     *
     * @return mixed
     */
    public function get($option);

    /**
     * Set a config option value.
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return void
     */
    public function set($option, $value);
}
