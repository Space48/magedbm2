<?php

namespace Meanbee\Magedbm2\Tests\Helper;

use Meanbee\Magedbm2\Helper\TablePatternExpander;
use PHPUnit\Framework\TestCase;

class TablePatternExpanderTest extends TestCase
{
    /** @var TablePatternExpander */
    private $subject;

    private $allTables = [];

    protected function setUp(): void
    {
        $this->subject = new TablePatternExpander();
        $this->allTables = [
            'admin',
            'admin_user',
            'admin_password',
            'sales_flat_order_1',
            'sales_flat_order_2',
            'sales_flat_order_3',
            'sales_flat_order_4',
            'sales_flat_order_5',
            'log_visitor',
            'test_visitor',
            'no_visitor',
            'sales_flat_quote'
        ];
    }

    public function testEmpty()
    {
        $this->assertCorrect([], []);
    }

    public function testNoWildcard()
    {
        $patterns = ['admin', 'admin_user'];
        $expected = ['admin', 'admin_user'];

        $this->assertCorrect($patterns, $expected);
    }

    public function testPrefixWildcard()
    {
        $patterns = ['admin*'];
        $expected = ['admin', 'admin_password', 'admin_user'];

        $this->assertCorrect($patterns, $expected);
    }

    public function testSuffixWildcard()
    {
        $patterns = ['*visitor'];
        $expected = ['log_visitor', 'no_visitor', 'test_visitor'];

        $this->assertCorrect($patterns, $expected);
    }

    public function testWrappedWildcard()
    {
        $patterns = ['*flat_order*'];
        $expected = ['sales_flat_order_1', 'sales_flat_order_2', 'sales_flat_order_3', 'sales_flat_order_4', 'sales_flat_order_5'];

        $this->assertCorrect($patterns, $expected);
    }

    /**
     * @param $patterns
     * @param $expected
     */
    private function assertCorrect($patterns, $expected)
    {
        $this->assertEquals($expected, $this->subject->expand($patterns, $this->allTables));
    }
}
