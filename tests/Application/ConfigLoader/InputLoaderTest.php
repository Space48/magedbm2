<?php

namespace Meanbee\Magedbm2\Tests\Application\ConfigLoader;

use Meanbee\Magedbm2\Application\ConfigInterface;
use Meanbee\Magedbm2\Application\ConfigLoader\InputLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class InputLoaderTest extends TestCase
{
    /** @var ConfigInterface */
    protected $config;

    protected function setUp()
    {
        $input = new ArrayInput([
            '--test' => 123
        ], new InputDefinition([
            new InputOption('test', null, InputOption::VALUE_REQUIRED),
            new InputOption('test-null', null, InputOption::VALUE_REQUIRED)
        ]));

        $this->config = (new InputLoader($input))->asConfig();
    }

    public function testProcess()
    {
        $this->assertEquals(123, $this->config->get('test'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMissingRequiredNotAdded()
    {
        $this->config->get('test-null');
    }
}
