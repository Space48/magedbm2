<?php

namespace Meanbee\Magedbm2\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Service\Anonymiser\Eav;

class EavRowProcessor extends RowProcessor
{
    private $entityCodeMap = [];
    private $attributeIdMap = [];
    private $formatterConfig = [];

    /**
     * @inheritdoc
     */
    public function process(Row $row)
    {
        $output  = "\t\t" .'<row>'. "\n";

        if (Eav::isValueTable($row->table)) {
            $output .= $this->processDataTable($row);
        } else {
            $output .= $this->processEntityTable($row);
        }

        $output .= "\t\t" .'</row>'. "\n";

        return $output;
    }

    public function addRule($eavEntity, $attributeCode, $formatter)
    {
        if (!array_key_exists($eavEntity, $this->formatterConfig)) {
            $this->formatterConfig[$eavEntity] = [];
        }

        $this->formatterConfig[$eavEntity][$attributeCode] = $formatter;
    }

    /**
     * @param $entityCode
     * @param $entityId
     */
    public function defineEntity($entityCode, $entityId)
    {
        $this->entityCodeMap[$entityCode] = $entityId;
    }

    /**
     * @param $attributeCode
     * @param $attributeId
     * @param $entityTypeId
     */
    public function defineAttribute($attributeCode, $attributeId, $entityTypeId)
    {
        if (!array_key_exists($entityTypeId, $this->attributeIdMap)) {
            $this->attributeIdMap[$entityTypeId] = [];
        }

        $this->attributeIdMap[$entityTypeId][$attributeId] = $attributeCode;
    }

    /**
     * @param Row $row
     * @return string
     */
    private function processDataTable(Row $row)
    {
        $entityCode = Eav::getEntityFromTable($row->table);
        $entityId = $this->getEntityIdByCode($entityCode);
        $attributeId = $row->get('attribute_id');
        $attributeCode = $this->getAttributeById($attributeId, $entityId);

        $output = '';

        foreach ($row->all() as $columnName => $columnValue) {
            if ($columnName === 'value') {
                $output .= $this->formattedAttributeValue($entityCode, $attributeCode, $columnValue);
            } else {
                $output .= $this->renderColumn($columnName, $columnValue);
            }
        }

        return $output;
    }

    /**
     * @param Row $row
     * @return string
     */
    private function processEntityTable(Row $row)
    {
        $output = '';
        $entityCode = Eav::getEntityFromTable($row->table);

        foreach ($row->all() as $columnName => $columnValue) {
            $output .= $this->renderColumn(
                $columnName,
                $this->runFormatter($columnValue, $this->getFormatterSpec($entityCode, $columnName))
            );
        }

        return $output;
    }

    /**
     * @param $entityCode
     * @return mixed
     */
    private function getEntityIdByCode($entityCode)
    {
        return $this->entityCodeMap[$entityCode] ?? null;
    }

    /**
     * @param $attributeId
     * @param $entityId
     * @return mixed
     */
    private function getAttributeById($attributeId, $entityId)
    {
        return $this->attributeIdMap[$entityId][$attributeId] ?? null;
    }

    /**
     * @param $entityTypeCode
     * @param $attributeCode
     * @return null
     */
    private function getFormatterSpec($entityTypeCode, $attributeCode)
    {
        if (isset($this->formatterConfig[$entityTypeCode][$attributeCode])) {
            return $this->formatterConfig[$entityTypeCode][$attributeCode];
        }

        return null;
    }

    /**
     * @param $entityCode
     * @param $attributeCode
     * @param $columnValue
     * @return string
     */
    private function formattedAttributeValue($entityCode, $attributeCode, $columnValue)
    {
        return $this->renderColumn(
            'value',
            $this->runFormatter($columnValue, $this->getFormatterSpec($entityCode, $attributeCode))
        );
    }
}
