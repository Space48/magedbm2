<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Person;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class Gender implements FormatterInterface
{
    /**
     * @param $value
     * @param array $rowContext
     * @return int
     */
    public function format($value, array $rowContext)
    {
        try {
            return random_int(1, 3);
        } catch (\Exception $e) {
            // Returns the index, so we need to add one.
            return array_rand([1, 2, 3]) + 1;
        }
    }
}
