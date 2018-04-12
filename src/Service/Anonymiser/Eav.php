<?php

namespace Meanbee\Magedbm2\Service\Anonymiser;

class Eav
{
    const VALUE_TYPES = ['datetime', 'decimal', 'int', 'text', 'varchar'];

    /**
     * Gets tht entity name from a table.
     *
     * @param $table
     * @return mixed|null
     */
    public static function getEntityFromTable($table)
    {
        if (strpos($table, '_entity') === false) {
            return null;
        }

        $entity = self::getEavParts($table)[0];
        $entity = str_replace('_entity', '', $entity);

        return $entity;
    }

    /**
     * Establish whether or not a given table looks like an EAV value table.
     *
     * @param $table
     * @return bool
     */
    public static function isValueTable($table)
    {
        $valueTable = self::getEavParts($table)[1];

        return in_array($valueTable, self::VALUE_TYPES, true);
    }

    private static function getEavParts($table)
    {
        $parts = explode('_entity_', $table);

        if (count($parts) > 1) {
            return [$parts[0], $parts[1]];
        }

        return [$parts[0], null];
    }
}
