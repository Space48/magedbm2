<?php

namespace Meanbee\Magedbm2\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Service\Anonymiser\Export\Row;
use Meanbee\Magedbm2\Service\Anonymiser\Export\RowProcessor;

class FlatRowProcessor extends RowProcessor
{
    private $formatterConfig = [];

    /**
     * @inheritdoc
     */
    public function process(Row $row)
    {
        $output =  "\t\t" . '<row>'. "\n";

        foreach ($row->all() as $columnName => $columnValue) {
            $output .= $this->formattedValue($row->table, $columnName, $columnValue);
        }

        $output .= "\t\t" .'</row>'. "\n";

        return $output;
    }

    /**
     * Define a formatter to be run when the $column in the $table is passed in for processing.
     *
     * @param $table
     * @param $column
     * @param $formatter
     */
    public function addRule($table, $column, $formatter)
    {
        if (!array_key_exists($table, $this->formatterConfig)) {
            $this->formatterConfig[$table] = [];
        }

        $this->formatterConfig[$table][$column] = $formatter;
    }

    /**
     * @param $table
     * @param $column
     * @param $value
     * @return string
     */
    private function formattedValue($table, $column, $value)
    {
        return $this->renderColumn(
            $column,
            $this->runFormatter($value, $this->getFormatterSpec($table, $column))
        );
    }

    /**
     * @param $table
     * @param $column
     * @return null
     */
    private function getFormatterSpec($table, $column)
    {
        if (isset($this->formatterConfig[$table][$column])) {
            return $this->formatterConfig[$table][$column];
        }

        return null;
    }
}
