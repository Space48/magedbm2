<?php

namespace Meanbee\Magedbm2\Service;

use Meanbee\Magedbm2\Exception\ConfigurationException;

interface ConfigurableServiceInterface
{
    /**
     * Evaluate whether the configuration for the database adapter is correct.
     *
     * @throws ConfigurationException
     */
    public function validateConfiguration(): bool;
}
