<?php

namespace Meanbee\Magedbm2\Test\Application;

use Meanbee\Magedbm2\Application\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGet()
    {
        $config = new Config([
            'test' => 123,
            'tables' => [
                1, 2, 3, 4, 5
            ],
            'nested' => [
                'one' => 'two'
            ],
            'dashes-in-name' => 'test'
        ]);

        $this->assertEquals(123, $config->get('test'));
        $this->assertCount(5, $config->get('tables'));
        $this->assertEquals('two', $config->get('nested.one'));
        $this->assertEquals('test', $config->get('dashes-in-name'));
    }

    public function testAll()
    {
        $config = new Config([
            'name' => 'Test',
            'nested' => [
                'one' => [
                    'two' => 'deep'
                ]
            ]
        ]);

        $allValues = $config->all();

        $this->assertEquals('Test', $allValues['name']);
        $this->assertEquals('deep', $allValues['nested']['one']['two']);
    }

    public function testMerge()
    {
        $config_1 = new Config([
            'one' => 'two',
            'nested' => [
                'one' => 'two'
            ],
            'deep_nesting' => [
                'level_1' => [
                    'level_2' => [
                        'level_3' => [
                            1, 2, 3, 4, 5
                        ],
                        'level_3a' => [
                            [
                                [
                                    ['a']
                                ]
                            ]
                        ]
                    ],
                    'level_2a' => [
                        11, 12, 13, 14, 15
                    ]
                ]
            ]
        ]);

        $config_2 = new Config([
            'three' => 'four',
            'deep_nesting' => [
                'level_1' => [
                    'level_2' => [
                        'level_3' => [
                            6, 7, 8, 9, 10
                        ],
                        'level_3a' => [
                            [
                                [
                                    ['b']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $config_3 = new Config([
            'five' => 'six',
            'one'  => 'seven',
            'nested' => [
                'three' => 'four'
            ]
        ]);

        $config_1->merge($config_2)->merge($config_3);

        $this->assertEquals('four', $config_1->get('three'));
        $this->assertEquals('six', $config_1->get('five'));
        $this->assertEquals('seven', $config_1->get('one'));

        $this->assertEquals('two', $config_1->get('nested.one'));
        $this->assertEquals('four', $config_1->get('nested.three'));

        $this->assertCount(10, $config_1->get('deep_nesting.level_1.level_2.level_3'));
        $this->assertCount(5, $config_1->get('deep_nesting.level_1.level_2a'));

        $this->assertCount(2, $config_1->get('deep_nesting.level_1.level_2.level_3a'));
        $this->assertEquals('a', $config_1->get('deep_nesting.level_1.level_2.level_3a.0.0.0.0'));
        $this->assertEquals('b', $config_1->get('deep_nesting.level_1.level_2.level_3a.1.0.0.0'));
    }

    public function testMergeNestedArrays()
    {
        $config_1 = new Config([
            'numeric' => [
                [ 'name' => 'first' ]
            ]
        ]);

        $config_2 = new Config([
            'numeric' => [
                [
                    'name' => 'second',
                    'additional_assoc_array' => [
                        'a' => 1234,
                        'b' => 4567
                    ]
                ],
                [
                    'name' => 'third',
                    'additional_array' => [0,1,2,3,4,5,6,7,8,9]
                ]
            ]
        ]);

        $config_1->merge($config_2);

        $this->assertCount(3, $config_1->get('numeric'));
        $this->assertEquals([
            [
                'name' => 'first'
            ],
            [
                'name' => 'second',
                'additional_assoc_array' => [
                    'a' => 1234,
                    'b' => 4567
                ]
            ],
            [
                'name' => 'third',
                'additional_array' => [0,1,2,3,4,5,6,7,8,9]
            ]
        ], $config_1->get('numeric'));
    }

    public function testOverrideInitialisedValue()
    {
        $initialConfig = new Config();
        $initialConfig->merge(new Config([
            'tmp-dir' => '/not/tmp'
        ]));

        $this->assertEquals('/not/tmp', $initialConfig->get('tmp-dir'));
    }
}
