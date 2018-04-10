<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Company;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class VatNumber implements FormatterInterface
{
    /**
     * @param $value
     * @param array $rowContext
     * @return mixed|string
     * @throws \Exception
     */
    public function format($value, array $rowContext)
    {
        // VAT numbers have check digits, but this is not implemented here in the interest of speed!
        return 'GB' . random_int(100000000, 999999999);
    }
}
