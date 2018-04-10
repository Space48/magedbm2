<?php

namespace Meanbee\Magedbm2\Anonymizer\Formatter\Password;

use Meanbee\Magedbm2\Anonymizer\FormatterInterface;

/**
 * Generates a consistent hash for the 'Password123'. This is implemented for speed. We're not interested in generating
 * real password hashes here, we're just satisfying the validation requirements on this field in the database, and
 * ensuring that we're not throwing around real password hashes in our exports.
 *
 * @package Meanbee\Magedbm2\Anonymizer\Formatter\Password
 */
class Simple implements FormatterInterface
{
    /**
     * @param $value
     * @param array $rowContext
     * @return string
     */
    public function format($value, array $rowContext)
    {
        /**
         * Password = Password123
         * Salt = OGQxYWQ4OTkxNTJkODBhOTliMTM4NDRm
         * Hash = hash('sha256', Salt + Password) = 3f3614302e4562adca809a3004925b93fe479046d557f2b5cdd0f83cf887fdba
         * Version = 1
         *
         * return Hash:Salt:Version
         */
        return '3f3614302e4562adca809a3004925b93fe479046d557f2b5cdd0f83cf887fdba:OGQxYWQ4OTkxNTJkODBhOTliMTM4NDRm:1';
    }
}
