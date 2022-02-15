<?php

namespace Meanbee\Magedbm2\Tests\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Service\Anonymiser\Export\EavRowProcessor;
use Meanbee\Magedbm2\Service\Anonymiser\Export\Row;
use PHPUnit\Framework\TestCase;

class EavRowProcessorTest extends TestCase
{
    /**
     * @var EavRowProcessor
     */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new EavRowProcessor();
        $this->subject->addRule('customer_address', 'email', 'Meanbee\Magedbm2\Anonymizer\Formatter\Rot13');
        $this->subject->defineEntity('customer_address', 100);
        $this->subject->defineAttribute('email', 200, 100);
    }

    public function testDataTableProcess()
    {
        $row = new Row();
        $row->table = 'customer_address_entity_varchar';
        $row->set('value_id', '18972349817234');
        $row->set('attribute_id', '200');
        $row->set('value', 'hello@test.com');

        $this->assertEquals(
            '<row><column name="value_id">18972349817234</column><column name="attribute_id">200</column><column name="value">uryyb@grfg.pbz</column></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    public function testEntityTableProcess()
    {
        $row = new Row();
        $row->table = 'customer_address_entity';
        $row->set('entity_id', '1');
        $row->set('city', 'CityM');
        $row->set('email', 'hello@test.com');

        $this->assertEquals(
            '<row><column name="entity_id">1</column><column name="city">CityM</column><column name="email">uryyb@grfg.pbz</column></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    public function testFormatsNull()
    {
        $row = new Row();
        $row->table = 'example';
        $row->set('iamnull', null);

        $this->assertEquals(
            '<row><column name="iamnull" xsi_type="nil" /></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    private function formatValue($value)
    {
        return str_replace(["\n", "\t"], '', $value);
    }
}
