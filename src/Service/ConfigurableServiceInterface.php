<?php

namespace Meanbee\Magedbm2\Service;

interface ConfigurableServiceInterface
{
    /**
     * Evaluate whether the configuration for the database adapter is correct.
     *
     * @throws ConfigurationException
     */
    public function validateConfiguration(): bool;
}
