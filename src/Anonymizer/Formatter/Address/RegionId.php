<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Address;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class RegionId implements FormatterInterface
{
    /**
     * @param $value
     * @param array $rowContext
     * @return string
     */
    public function format($value, array $rowContext)
    {
        return 1;
    }
}
