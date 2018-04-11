<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Person;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;
use Meanbee\Magedbm2\Anonymizer\RandomGeneratorTrait;

class Gender implements FormatterInterface
{
    use RandomGeneratorTrait;

    /**
     * @param $value
     * @param array $rowContext
     * @return int
     * @throws \InvalidArgumentException
     */
    public function format($value, array $rowContext)
    {
        return $this->randomInteger(1, 3);
    }
}
