<?php
namespace Meanbee\Magedbm2\Tests\Command;

use Meanbee\Magedbm2\Application\Config;
use Meanbee\Magedbm2\Command\ViewConfigurationCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ViewConfigurationCommandTest extends AbstractCommandTest
{
    public function testCommand()
    {
        $tester = new CommandTester(new ViewConfigurationCommand(
            new Config(['test' => 'test123'])
        ));

        $tester->execute([]);

        $this->assertContains('test: test123', $tester->getDisplay());
    }
}