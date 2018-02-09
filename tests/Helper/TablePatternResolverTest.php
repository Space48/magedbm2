<?php

namespace Meanbee\Magedbm2\Tests\Helper;

use Meanbee\Magedbm2\Helper\TablePatternResolver;
use PHPUnit\Framework\TestCase;

class TablePatternResolverTest extends TestCase
{
    /** @var TablePatternResolver */
    private $subject;

    private $allTables = [];

    protected function setUp()
    {
        $this->subject = new TablePatternResolver();
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

    public function test_empty()
    {
        $this->assertCorrect([], []);
    }

    public function test_no_wildcard()
    {
        $patterns = ['admin', 'admin_user'];
        $expected = ['admin', 'admin_user'];

        $this->assertCorrect($patterns, $expected);
    }

    public function test_prefix_wildcard()
    {
        $patterns = ['admin*'];
        $expected = ['admin', 'admin_password', 'admin_user'];

        $this->assertCorrect($patterns, $expected);
    }

    public function test_suffix_wildcard()
    {
        $patterns = ['*visitor'];
        $expected = ['log_visitor', 'no_visitor', 'test_visitor'];

        $this->assertCorrect($patterns, $expected);
    }

    public function test_wrapped_wildcard()
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
        $this->assertEquals($expected, $this->subject->resolve($patterns, $this->allTables));
    }
}
