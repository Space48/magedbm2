<?php

namespace Meanbee\Magedbm2\Tests\Service\Filesystem;

use Meanbee\Magedbm2\Service;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    protected $test_dir = __DIR__ . DIRECTORY_SEPARATOR . "tmp";

    protected function setUp(): void
    {
        parent::setUp();

        // Create test directory
        mkdir($this->test_dir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Remove test directory and files
        $iterator = new \RecursiveDirectoryIterator($this->test_dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($this->test_dir);

        parent::tearDown();
    }

    public function testWrite()
    {
        $filesystem = new Service\Filesystem\Simple();

        $file = implode(DIRECTORY_SEPARATOR, [$this->test_dir, "output-file.txt"]);
        $contents = sprintf("Test file output. Timestamp %s.", time());

        $this->assertFileNotExists($file);

        $filesystem->write($file, $contents);

        $this->assertStringEqualsFile($file, $contents);
    }

    /**
     * Test moving file to a new location.
     *
     * @test
     */
    public function testMove()
    {
        $filesystem = new Service\Filesystem\Simple();

        $source = implode(DIRECTORY_SEPARATOR, [$this->test_dir, "source-file.txt"]);
        $destination = implode(DIRECTORY_SEPARATOR, [$this->test_dir, "dest", "dest-file.txt"]);

        file_put_contents($source, sprintf("Test file written by %s", __METHOD__));

        $this->assertFileExists($source);
        $this->assertFileNotExists($destination);

        $filesystem->move($source, $destination);

        $this->assertFileExists($destination);
        $this->assertFileNotExists($source);
    }

    /**
     * Test deleting a file.
     *
     * @test
     */
    public function testDelete()
    {
        $filesystem = new Service\Filesystem\Simple();

        $file = implode(DIRECTORY_SEPARATOR, [$this->test_dir, "test-file.txt"]);

        file_put_contents($file, sprintf("Test file written by %s", __METHOD__));

        $this->assertFileExists($file);

        $filesystem->delete($file);

        $this->assertFileNotExists($file);
    }
}
