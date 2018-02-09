<?php

namespace Meanbee\Magedbm2\Helper;

use Meanbee\Magedbm2\Application\Config\TableGroup;

class TableGroupExpander
{
    /**
     * @var TableGroup[]
     */
    private $tableGroups;
    
    /**
     * @param TableGroup[] $tableGroups
     */
    public function __construct(array $tableGroups = [])
    {
        $this->tableGroups = $tableGroups;
    }

    /**
     * @param TableGroup[] $tableGroups
     */
    public function setTableGroups(array $tableGroups)
    {
        $this->tableGroups = $tableGroups;
    }
    
    /**
     * @inheritdoc
     */
    public function expand($tables = '')
    {
        $tableDefinitions = explode(' ', $tables);
        
        foreach ($tableDefinitions as $idx => $table) {
            if ($this->looksLikeTableDefinition($table)) {
                $tableGroup  = $this->getTableDefinition($table);
                
                if ($tableGroup !== null) {
                    $tableDefinitions[$idx] = implode(' ', $tableGroup->getTables());
                } else {
                    $tableDefinitions[$idx] = '';
                }
            }
        }

        $tableDefinitions = array_map('trim', $tableDefinitions);
        
        $tableDefinitionString = implode(' ', $tableDefinitions);

        if (!$this->containsTableDefinition($tableDefinitionString)) {
            return $tableDefinitionString;
        }

        return $this->expand($tableDefinitionString);
    }
    
    /**
     * @param $string
     * @return bool
     */
    protected function looksLikeTableDefinition($string): bool
    {
        return 0 === strpos($string, '@');
    }

    /**
     * @param $string
     * @return bool
     */
    protected function containsTableDefinition($string): bool
    {
        return strpos($string, '@') !== false;
    }
    
    /**
     * @param $string
     * @return TableGroup|null
     */
    protected function getTableDefinition($string)
    {
        $tableGroupId = substr($string, 1);
        
        foreach ($this->tableGroups as $tableGroup) {
            if ($tableGroup->getId() === $tableGroupId) {
                return $tableGroup;
            }
        }
        
        return null;
    }
}
