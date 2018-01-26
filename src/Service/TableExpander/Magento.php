<?php

namespace Meanbee\Magedbm2\Service\TableExpander;

use Meanbee\Magedbm2\Application\Config\TableGroup;
use Meanbee\Magedbm2\Service\TableExpanderInterface;

class Magento implements TableExpanderInterface
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
                }
            }
        }
        
        return implode(' ', $tableDefinitions);
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
     * @return TableGroup|null
     */
    protected function getTableDefinition($string) {
        $tableGroupId = substr($string, 1);
        
        foreach ($this->tableGroups as $tableGroup) {
            if ($tableGroup->getId() === $tableGroupId) {
                return $tableGroup;
            }
        }
        
        return null;
    }
}
