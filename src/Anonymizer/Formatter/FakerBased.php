<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter;

use Faker\Factory as FakerFactory;

/**
 * @internal
 */
abstract class FakerBased
{
    private static $faker;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create();
        }
    }

    /**
     * @return \Faker\Generator
     */
    protected function getFaker()
    {
        return self::$faker;
    }
}
