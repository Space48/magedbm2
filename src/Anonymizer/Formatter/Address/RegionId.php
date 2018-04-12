<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Address;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;
use Meanbee\Magedbm2\Anonymizer\RandomGeneratorTrait;

class RegionId implements FormatterInterface
{
    use RandomGeneratorTrait;

    /**
     * Generate a region id WITHOUT factoring the country_id of the row.
     *
     * @param $value
     * @param array $rowContext
     * @return string
     * @throws \InvalidArgumentException
     */
    public function format($value, array $rowContext)
    {
        // There are about 550 different regions across all countries in Magento 2.2. Hopefully this formatter, without
        // context of the rest of the row, will be good enough for your data!
        return $this->randomInteger(1, 550);
    }
}
