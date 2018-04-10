<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Person;

use Meanbee\Magedbm2\Anonymizer\Formatter\FakerBased;
use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class UniqueEmail extends FakerBased implements FormatterInterface
{
    /**
     * Generate a unique email address.
     *
     * @param $value
     * @param array $rowContext
     * @return string
     */
    public function format($value, array $rowContext)
    {
        return $this->getFaker()->unique()->email;
    }
}
