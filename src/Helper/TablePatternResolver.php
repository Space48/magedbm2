<?php

namespace Meanbee\Magedbm2\Helper;

class TablePatternResolver
{
    /**
     * Consume patterns like 'admin*', 'log_*' and '*_flat_*' and return matching tables.
     *
     * @param array $tablePatterns
     * @param array $tables
     * @return array
     */
    public function resolve(array $tablePatterns, array $tables)
    {
        sort($tablePatterns);
        sort($tables);

        $matchedTables = [];

        foreach ($tablePatterns as $tablePattern) {
            $tablePattern = str_replace('*', '.*', $tablePattern);
            $tablePattern = '/^' . $tablePattern . '$/';

            foreach (preg_grep($tablePattern, $tables) as $matchedTable) {
                $matchedTables[] = $matchedTable;
            }
        }

        return $matchedTables;
    }
}
