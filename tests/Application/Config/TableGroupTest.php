<?php

namespace Meanbee\Magedbm2\Tests\Application\Config;

use Meanbee\Magedbm2\Application\Config\TableGroup;
use PHPUnit\Framework\TestCase;

class TableGroupTest extends TestCase
{
    public function testWithWhitespaceCharacters()
    {
        $yaml = <<<YAML
      sales_order
        sales_order_address
        sales_order_aggregated_created
        sales_order_aggregated_updated
        sales_order_grid
        sales_order_item
YAML;

        $tableGroup = new TableGroup('test', 'test', $yaml);

        $this->assertCount(6, $tableGroup->getTables());

        $this->assertContains('sales_order', $tableGroup->getTables());
        $this->assertContains('sales_order_address', $tableGroup->getTables());
        $this->assertContains('sales_order_aggregated_created', $tableGroup->getTables());
        $this->assertContains('sales_order_aggregated_updated', $tableGroup->getTables());
        $this->assertContains('sales_order_grid', $tableGroup->getTables());
        $this->assertContains('sales_order_item', $tableGroup->getTables());
    }
}
