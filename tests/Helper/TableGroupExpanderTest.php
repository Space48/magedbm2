<?php

namespace Meanbee\Magedbm2\Tests\Helper\TableExpander;

use Meanbee\Magedbm2\Application\Config\TableGroup;
use Meanbee\Magedbm2\Helper\TableGroupExpander;
use PHPUnit\Framework\TestCase;

class TableGroupExpanderTest extends TestCase
{
    /**
     * @var TableGroupExpander
     */
    protected $subject;
    
    protected function setUp(): void
    {
        $this->subject = new TableGroupExpander([
            new TableGroup('example', '', 'table1 table2 table3_ table4*'),
            new TableGroup('example2', '', 'cow_* foo_*'),
            new TableGroup('example3', '', '@example @example2'),
            new TableGroup('example4', '', '@example3'),
        ]);
    }
    
    public function testHandlesEmpty()
    {
        $this->assertEquals('', $this->subject->expand(''));
    }
    
    public function testHandlesTableNames()
    {
        $this->assertEquals('table1', $this->subject->expand('table1'));
        $this->assertEquals('table1 table2', $this->subject->expand('table1 table2'));
        $this->assertEquals('table1 table2 table*', $this->subject->expand('table1 table2 table*'));
    }
    
    public function testHandlesTableGroups()
    {
        $this->assertEquals('table1 table2 table3_ table4*', $this->subject->expand('@example'));
        $this->assertEquals('cow_* foo_*', $this->subject->expand('@example2'));
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example @example2'));
    }
    
    public function testHandlesMixOfBoth()
    {
        $this->assertEquals('aaa table1 table2 table3_ table4* cow_* foo_* zzz', $this->subject->expand('aaa @example @example2 zzz'));
    }
    
    public function testRemovesUndefinedTableGroup()
    {
        $this->assertEquals('', $this->subject->expand('@moo'));
    }

    public function testRecursiveTableDefinition()
    {
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example3'));
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example4'));
    }
}
