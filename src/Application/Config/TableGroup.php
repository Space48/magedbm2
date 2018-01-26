<?php

namespace Meanbee\Magedbm2\Application\Config;

class TableGroup
{
    private $id;
    private $description;
    private $tables;
    
    public function __construct(string $id, string $description, string $tables)
    {
        $this->id = $id;
        $this->description = $description;
        $this->tables = explode(' ', $tables);
    }
    
    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @return array
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
