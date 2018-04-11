<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Company;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;
use Meanbee\Magedbm2\Anonymizer\RandomGeneratorTrait;

class VatNumber implements FormatterInterface
{
    use RandomGeneratorTrait;

    /**
     * @param $value
     * @param array $rowContext
     * @return mixed|string
     * @throws \InvalidArgumentException
     */
    public function format($value, array $rowContext)
    {
        // VAT numbers have check digits, but this is not implemented here in the interest of speed!
        return 'GB' . $this->randomInteger(100000000, 999999999);
    }
}
