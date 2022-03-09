<?php

namespace Meanbee\Magedbm2\Tests\Application\ConfigLoader;

use Meanbee\Magedbm2\Application\ConfigLoader\FileLoader;
use PHPUnit\Framework\TestCase;

class FileLoaderTest extends TestCase
{
    /**
     * @throws \Meanbee\Magedbm2\Exception\ConfigurationException
     */
    public function testSimpleLoad()
    {
        $config = (new FileLoader($this->getFilePath('simple.yml')))->asConfig();

        $this->assertEquals('two', $config->get('one'));
        $this->assertEquals('four', $config->get('three'));
        $this->assertEquals('six', $config->get('five'));
    }

    public function testNonexistentFile()
    {
        $this->expectException(\Meanbee\Magedbm2\Exception\ConfigurationException::class);
        (new FileLoader($this->getFilePath('idontexist.yml')))->asConfig();
    }

    /**
     * @param $path
     * @return bool|string
     */
    private function getFilePath($path)
    {
        $fullPath = implode(DIRECTORY_SEPARATOR, [
            __DIR__, 'FileLoaderTest', $path
        ]);

        return $fullPath;
    }
}
