<?php

namespace Meanbee\Magedbm2\Tests\Service\Storage;

use Aws\S3\S3Client;
use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\Storage\Local;
use Meanbee\Magedbm2\Service\Storage\S3;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class LocalTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    protected function setUp()
    {
        $this->app = new Application();
    }

    public function testListProjects()
    {
        $localStorage = new Local();
        $localStorage->setTmpDir(__DIR__. "/_data/projects");

        $localStorageMock = $this->createMock(Local::class);
        $localStorageMock->expects($this->once())
            ->method("listProjects")
            ->willReturn([
            '.', '..', 'test', 'test2'
        ]);
        self::assertEquals(
            $localStorageMock->listProjects(),
            $localStorage->listProjects(),
            'List projects does not return the same directores'
        );
    }
}