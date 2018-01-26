<?php

namespace Meanbee\Magedbm2\Service;

interface TableExpanderInterface
{
    /**
     * @param string $tableDefinitions
     * @return string
     */
    public function expand($tableDefinitions = '');
}
