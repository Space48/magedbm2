<?php

namespace Meanbee\Magedbm2\Tests\Service\Anonymiser;

use Meanbee\Magedbm2\Service\Anonymiser\Eav;
use PHPUnit\Framework\TestCase;

class EavTest extends TestCase
{
    public function testGetEntityFromTable()
    {
        $this->assertNull(Eav::getEntityFromTable('boo'));
        $this->assertNull(Eav::getEntityFromTable(null));
        $this->assertNull(Eav::getEntityFromTable(''));
        $this->assertNull(Eav::getEntityFromTable(false));
        $this->assertNull(Eav::getEntityFromTable(true));

        $this->assertEquals('customer', Eav::getEntityFromTable('customer_entity'));
        $this->assertEquals('customer', Eav::getEntityFromTable('customer_entity_decimal'));
        $this->assertEquals('catalog_product', Eav::getEntityFromTable('catalog_product_entity_varchar'));
        $this->assertEquals('customer_address', Eav::getEntityFromTable('customer_address_entity'));
    }

    public function testIsValueTable()
    {
        $this->assertTrue(Eav::isValueTable('catalog_category_entity_datetime'));
        $this->assertTrue(Eav::isValueTable('catalog_category_entity_decimal'));
        $this->assertTrue(Eav::isValueTable('catalog_category_entity_int'));
        $this->assertTrue(Eav::isValueTable('catalog_category_entity_text'));
        $this->assertTrue(Eav::isValueTable('catalog_category_entity_varchar'));

        $this->assertFalse(Eav::isValueTable('catalog_category'));
        $this->assertFalse(Eav::isValueTable('catalog_category_entity'));
        $this->assertFalse(Eav::isValueTable('moo'));

        $this->assertFalse(Eav::isValueTable(false));
        $this->assertFalse(Eav::isValueTable(true));
        $this->assertFalse(Eav::isValueTable(null));
        $this->assertFalse(Eav::isValueTable(0));
        $this->assertFalse(Eav::isValueTable(''));
    }
}
