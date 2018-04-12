<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class Blank implements FormatterInterface
{
    /**
     * Return an empty string.
     *
     * @param $value
     * @param array $rowContext
     * @return string
     */
    public function format($value, array $rowContext)
    {
        return '';
    }
}
