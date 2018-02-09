<?php

namespace Meanbee\Magedbm2\Tests\Service\TableExpander;

use Meanbee\Magedbm2\Application\Config\TableGroup;
use Meanbee\Magedbm2\Service\TableExpander\Magento as MagentoTableExpander;
use PHPUnit\Framework\TestCase;

class MagentoTest extends TestCase
{
    /**
     * @var MagentoTableExpander
     */
    protected $subject;
    
    protected function setUp()
    {
        $this->subject = new MagentoTableExpander([
            new TableGroup('example', '', 'table1 table2 table3_ table4*'),
            new TableGroup('example2', '', 'cow_* foo_*'),
            new TableGroup('example3', '', '@example @example2'),
            new TableGroup('example4', '', '@example3'),
        ]);
    }
    
    public function test_handles_empty()
    {
        $this->assertEquals('', $this->subject->expand(''));
    }
    
    public function test_handles_table_names()
    {
        $this->assertEquals('table1', $this->subject->expand('table1'));
        $this->assertEquals('table1 table2', $this->subject->expand('table1 table2'));
        $this->assertEquals('table1 table2 table*', $this->subject->expand('table1 table2 table*'));
    }
    
    public function test_handles_table_groups()
    {
        $this->assertEquals('table1 table2 table3_ table4*', $this->subject->expand('@example'));
        $this->assertEquals('cow_* foo_*', $this->subject->expand('@example2'));
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example @example2'));
    }
    
    public function test_handles_mix_of_both()
    {
        $this->assertEquals('aaa table1 table2 table3_ table4* cow_* foo_* zzz', $this->subject->expand('aaa @example @example2 zzz'));
    }
    
    public function test_removes_undefined_table_group()
    {
        $this->assertEquals('', $this->subject->expand('@moo'));
    }

    public function test_recursive_table_definition()
    {
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example3'));
        $this->assertEquals('table1 table2 table3_ table4* cow_* foo_*', $this->subject->expand('@example4'));
    }
}
