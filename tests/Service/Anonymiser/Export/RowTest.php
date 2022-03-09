<?php

namespace Meanbee\Magedbm2\Tests\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Service\Anonymiser\Export\Row;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    /** @var Row */
    private $subject;

    public function setUp(): void
    {
        $this->subject = Row::fromString('
	<row>
		<field name="entity_id">93</field>
		<field name="increment_id" xsi:nil="true" />
		<field name="parent_id">46</field>
		<field name="created_at">2018-03-16 15:18:52</field>
		<field name="updated_at">2018-03-16 15:18:52</field>
		<field name="is_active">1</field>
		<field name="city">CityM</field>
		<field name="company">CompanyName</field>
		<field name="country_id">US</field>
		<field name="fax"></field>
		<field name="firstname">John</field>
		<field name="lastname">Smith</field>
		<field name="middlename" xsi:nil="true" />
		<field name="postcode">18670</field>
		<field name="prefix" xsi:nil="true" />
		<field name="region">Alabama</field>
		<field name="region_id">1</field>
		<field name="street">Green str, 67</field>
		<field name="suffix" xsi:nil="true" />
		<field name="telephone">3468676</field>
		<field name="vat_id" xsi:nil="true" />
		<field name="vat_is_valid" xsi:nil="true" />
		<field name="vat_request_date" xsi:nil="true" />
		<field name="vat_request_id" xsi:nil="true" />
		<field name="vat_request_success" xsi:nil="true" />
	</row>
');
    }

    public function testFieldParsing()
    {
        $this->assertEquals(93, $this->subject->get('entity_id'));
        $this->assertNull($this->subject->get('increment_id'));
        $this->assertNotNull($this->subject->get('fax'));
        $this->assertEquals('', $this->subject->get('fax'));

        $this->assertCount(25, $this->subject->all());
    }
}
