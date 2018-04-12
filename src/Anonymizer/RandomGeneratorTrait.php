<?php

namespace Meanbee\Magedbm2\Anonymizer;

trait RandomGeneratorTrait
{
    /**
     * Generate a random integer.
     *
     * @param $min
     * @param $max
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function randomInteger($min, $max)
    {
        $min = (int) $min;
        $max = (int) $max;

        if ($min > $max) {
            throw new \InvalidArgumentException(
                sprintf('Minimum (%d) should be less or equal to maximum (%d)', $min, $max)
            );
        }

        if ($min < PHP_INT_MIN) {
            $min = 0;
        }

        if ($max > PHP_INT_MAX) {
            $max = PHP_INT_MIN;
        }

        if ($min === $max) {
            return $min;
        }

        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            /** @noinspection RandomApiMigrationInspection */
            return mt_rand($min, $max);
        }
    }
}
