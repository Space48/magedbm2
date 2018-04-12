<?php

namespace Meanbee\Magedbm2\Tests\Service\Anonymiser;

use Meanbee\Magedbm2\Service\Anonymiser\Export;
use PHPUnit\Framework\TestCase;
use VirtualFileSystem\FileSystem as VirtualFileSystem;

class ExportTest extends TestCase
{
    const NOOP = 'Meanbee\Magedbm2\Anonymizer\Formatter\Noop';

    /** @var Export */
    private $subject;

    /** @var VirtualFileSystem */
    private $vfs;

    private $inputFile;
    private $outputFile;

    public function setUp()
    {
        $this->subject = new Export();

        $this->vfs = new VirtualFileSystem();

        $this->inputFile = $this->getDataFilePath('test.xml');
        $this->outputFile = $this->vfs->path('/output.xml');

        $this->subject->addColumnRule('sales_order', 'customer_firstname', self::NOOP);
        $this->subject->addColumnRule('sales_order_address', 'firstname', self::NOOP);

        $this->subject->addAttributeRule('customer', 'firstname', self::NOOP);
        $this->subject->addAttributeRule('customer_address', 'firstname', self::NOOP);

        @unlink($this->outputFile);
    }

    public function testProcessFile()
    {
        try {
            $this->subject->processFile($this->inputFile, $this->outputFile);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertGreaterThan(
            0,
            filesize($this->outputFile),
            sprintf('Expected %s to have a non-zero file size', $this->outputFile)
        );

        $inputXml = new \SimpleXMLElement(file_get_contents($this->inputFile));
        $outputXml = new \SimpleXMLElement(file_get_contents($this->outputFile));

        $tablesToCheck = [
            'sales_order',
            'sales_order_address',
            'customer_entity',
            'customer_entity_datetime',
            'customer_entity_decimal',
            'customer_entity_int',
            'customer_entity_text',
            'customer_entity_varchar',
            'customer_address_entity',
            'customer_address_entity_datetime',
            'customer_address_entity_decimal',
            'customer_address_entity_int',
            'customer_address_entity_text',
            'customer_address_entity_varchar',
        ];

        foreach ($tablesToCheck as $table) {
            $inputRowCountPath = sprintf("//table_data[@name='%s']/row", $table);
            $outputRowCountPath = sprintf("//table[@name='%s']/row", $table);

            $inputRowCount  = count($inputXml->xpath($inputRowCountPath));
            $outputRowCount = count($outputXml->xpath($outputRowCountPath));

            $this->assertEquals(
                $inputRowCount,
                $outputRowCount,
                sprintf('Expected same number of rows for %s in input and output', $table)
            );
        }
    }

    public function testFormatterApplied()
    {
        $this->subject->addColumnRule(
            'sales_order',
            'customer_email',
            'Meanbee\Magedbm2\Anonymizer\Formatter\Rot13'
        );

        $this->subject->processFile($this->inputFile, $this->outputFile);

        $inputXml = new \SimpleXMLElement(file_get_contents($this->inputFile));
        $outputXml = new \SimpleXMLElement(file_get_contents($this->outputFile));

        $inputOrderIdPath = "//table_data[@name='sales_order']/row[1]/field[@name='increment_id']/text()";
        $inputEmailPath = "//table_data[@name='sales_order']/row[1]/field[@name='customer_email']/text()";

        $outputOrderIdPath = "//table[@name='sales_order']/row[1]/column[@name='increment_id']/text()";
        $outputEmailPath = "//table[@name='sales_order']/row[1]/column[@name='customer_email']/text()";

        $inputOrderId = (string) $inputXml->xpath($inputOrderIdPath)[0];
        $inputEmail = (string) $inputXml->xpath($inputEmailPath)[0];

        $outputOrderId = (string) $outputXml->xpath($outputOrderIdPath)[0];
        $outputEmail = (string) $outputXml->xpath($outputEmailPath)[0];

        $this->assertEquals('200000001', $inputOrderId);
        $this->assertEquals($inputOrderId, $outputOrderId);

        $this->assertEquals('order_1@example.com', $inputEmail);
        $this->assertEquals('beqre_1@rknzcyr.pbz', $outputEmail);
    }

    public function testAddsFlatTable()
    {
        $newTables = $this->subject->addColumnRule('mytable', 'test', 'test');

        $this->assertCount(1, $newTables);
        $this->assertEquals('mytable', $newTables[0]);
    }

    public function testAddsEavTables()
    {
        $newTables = $this->subject->addAttributeRule('customer', 'test', 'test');

        $this->assertCount(6, $newTables);

        $this->assertContains('customer_entity', $newTables);
        $this->assertContains('customer_entity_datetime', $newTables);
        $this->assertContains('customer_entity_decimal', $newTables);
        $this->assertContains('customer_entity_int', $newTables);
        $this->assertContains('customer_entity_text', $newTables);
        $this->assertContains('customer_entity_varchar', $newTables);
    }

    /**
     * Return the file path to a data file.
     *
     * @param $name
     * @return string
     */
    private function getDataFilePath($name)
    {
        $filePath = implode(DIRECTORY_SEPARATOR, [__DIR__, '_data', $name]);

        if (!file_exists($filePath)) {
            $this->fail(sprintf('Unable to load data file %s, file doesn\'t exist at %s', $name, $filePath));
        }

        return $filePath;
    }
}
