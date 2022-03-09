<?php

namespace Meanbee\Magedbm2\Tests\Service\Anonymiser\Export;

use Meanbee\Magedbm2\Service\Anonymiser\Export\FlatRowProcessor;
use Meanbee\Magedbm2\Service\Anonymiser\Export\Row;
use PHPUnit\Framework\TestCase;

class FlatRowProcessorTest extends TestCase
{
    /**
     * @var FlatRowProcessor
     */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new FlatRowProcessor();
        $this->subject->addRule('customer', 'email', 'Meanbee\Magedbm2\Anonymizer\Formatter\Rot13');
    }

    public function testProcess()
    {
        $row = new Row();
        $row->table = 'customer';
        $row->set('foo', 'bar');
        $row->set('email', 'hello@test.com');

        $this->assertEquals(
            '<row><column name="foo">bar</column><column name="email">uryyb@grfg.pbz</column></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    public function testCData()
    {
        $row = new Row();
        $row->table = 'customer';
        $row->set('test', '<<>>');
        $row->set('email', '>><<');

        $this->assertEquals(
            '<row><column name="test"><![CDATA[<<>>]]></column><column name="email"><![CDATA[>><<]]></column></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    public function testNil()
    {
        $row = new Row();
        $row->table = 'customer';
        $row->set('test', null);
        $row->set('email', null);
        $row->set('blank', '');

        $this->assertEquals(
            '<row><column name="test" xsi_type="nil" /><column name="email" xsi_type="nil" /><column name="blank"></column></row>',
            $this->formatValue($this->subject->process($row))
        );
    }

    private function formatValue($value)
    {
        return str_replace(["\n", "\t"], '', $value);
    }
}
