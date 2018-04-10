<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class Rot13 implements FormatterInterface
{
    /**
     * Return a rot13'd string.
     *
     * @param $value
     * @param array $rowContext
     * @return mixed|string
     */
    public function format($value, array $rowContext)
    {
        if ('' === trim($value)) {
            return '';
        }

        return str_rot13($value);
    }
}
