<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

class DateTime implements FormatterInterface
{
    /**
     * Get a random MySQL formatted datetime between the the unix epoch and when the function is run.
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

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($this->getRandomInteger(0, time()));

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param $min
     * @param $max
     *
     * @return int
     */
    private function getRandomInteger($min, $max)
    {
        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            /** @noinspection RandomApiMigrationInspection */
            return mt_rand($min, $max);
        }
    }
}
