<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Person;

use Meanbee\Magedbm2\Anonymizer\Formatter\FakerBased;
use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class FirstName extends FakerBased implements FormatterInterface
{
    /**
     * Get a random first name.
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

        return $this->getFaker()->firstName();
    }
}
