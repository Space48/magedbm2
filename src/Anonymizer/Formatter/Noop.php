<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class Noop implements FormatterInterface
{
    /**
     * Do nothing.
     *
     * @param $value
     * @param array $rowContext
     * @return mixed|string
     */
    public function format($value, array $rowContext)
    {
        return $value;
    }
}
