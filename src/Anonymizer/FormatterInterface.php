<?php

namespace Meanbee\Magedbm2\Anonymizer;

interface FormatterInterface
{
    /**
     * @param $value
     * @param array $rowContext
     * @return mixed
     */
    public function format($value, array $rowContext);
}
