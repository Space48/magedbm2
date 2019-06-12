<?php

namespace Meanbee\Magedbm2\Tests\Service\Storage;

use Aws\S3\S3Client;
use Meanbee\Magedbm2\Service\Storage\DigitalOceanSpaces;
use Meanbee\Magedbm2\Application;
use Meanbee\Magedbm2\Service\Storage\Data\File;
use Meanbee\Magedbm2\Service\Storage\S3;
use Meanbee\Magedbm2\Service\StorageInterface;
use PHPUnit\Framework\TestCase;

class DOTest extends TestCase
{

    /** @var Application\Config */
    public $config;

    protected function setUp()
    {
        $this->config = new Application\Config([
            'selected-storage-adapter' => 'digitalocean-spaces',
            'storage-adapters' => [
                'digitalocean-spaces' => [
                    'bucket' => 'test-bucket',
                    'data-bucket' => 'test',
                    'region' => 'ams3',
                    'space' => 'test',
                    'access-key' => 12345,
                    'secret-key' => 12345
                ]
            ]
        ]);
    }

    /**
     * Test that the service sets the required options on the application.
     *
     * @test
     */
    public function testConsoleOptions()
    {
        $app = new Application();

        $service = new DigitalOceanSpaces($app, $this->getConfigMock());

        $options = [
            "access-key",
            "secret-key",
            "region",
            "space"
        ];

        foreach ($options as $option) {
            $this->assertTrue(
                $app->getDefinition()->hasOption($option),
                sprintf("Expected application option '%s' not found.", $option)
            );
        }
    }

    /**
     * Test that project names are extracted from available objects.
     *
     * @test
     */
    public function testListProjects()
    {
        $app = new Application();

        $client = $this->createMock(S3Client::class);
        $client
            ->expects($this->once())
            ->method("getIterator")
            ->with(
                $this->equalTo("ListObjects"),
                $this->equalTo(["Bucket" => "test-bucket"])
            )
            ->willReturn([
                ["Key" => "test-project-1/backup-file-1.sql.gz"],
                ["Key" => "test-project-2/backup-file-1.sql.gz"],
                ["Key" => "test-project-1/backup-file-2.sql.gz"],
                ["Key" => "test-project-foo/backup-file-1.sql.gz"],
                ["Key" => "test-project-foo/backup-file-2.sql.gz"],
                ["Key" => "test-project-bar/backup-file-1.sql.gz"],
            ]);

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $this->assertEquals(
            [
                "test-project-1",
                "test-project-2",
                "test-project-foo",
                "test-project-bar",
            ],
            $service->listProjects(),
            "Returned projects list does not match the expected list."
        );
    }

    /**
     * Test that available objects get filtered for a project.
     *
     * @test
     */
    public function testListFiles()
    {
        $app = new Application();

        $client = $this->createMock(S3Client::class);
        $client
            ->expects($this->once())
            ->method("getIterator")
            ->with(
                $this->equalTo("ListObjects"),
                $this->equalTo([
                    "Bucket" => "test-bucket",
                    "Prefix" => "test-project-1/",
                ])
            )
            ->willReturn([
                [
                    "Key" => "test-project-1/backup-file-1.sql.gz",
                    "Size" => 1000000000,
                    "LastModified" => new \DateTime("1999-09-05 12:34:56"),
                ],
                [
                    "Key" => "test-project-1/backup-file-2.sql.gz",
                    "Size" => 1000000000,
                    "LastModified" => new \DateTime("2017-05-22 12:34:56"),
                ],
            ]);

        $service = new S3($app, $this->config, $client);

        $file1 = new File();
        $file1->name = "backup-file-1.sql.gz";
        $file1->project = "test-project-1";
        $file1->size = 1000000000;
        $file1->last_modified = new \DateTime("1999-09-05 12:34:56");

        $file2 = new File();
        $file2->name = "backup-file-2.sql.gz";
        $file2->project = "test-project-1";
        $file2->size = 1000000000;
        $file2->last_modified = new \DateTime("2017-05-22 12:34:56");

        $expected = [$file1, $file2];

        $this->assertEquals(
            $expected,
            $service->listFiles("test-project-1"),
            "The method did not return the expected list of files."
        );
    }

    /**
     * Test that the latest file is correctly identified.
     *
     * @test
     */
    public function testGetLatestFile()
    {
        $app = new Application();

        $client = $this->createMock(S3Client::class);
        $client
            ->expects($this->once())
            ->method("getIterator")
            ->with(
                $this->equalTo("ListObjects"),
                $this->equalTo([
                    "Bucket" => "test-bucket",
                    "Prefix" => "test-project-1/",
                ])
            )
            ->willReturn([
                [
                    "Key" => "test-project-1/backup-file-1.sql.gz",
                    "Size" => 1000000000,
                    "LastModified" => new \DateTime("1999-09-05 12:34:56"),
                ],
                [
                    "Key" => "test-project-1/backup-file-2.sql.gz",
                    "Size" => 1000000000,
                    "LastModified" => new \DateTime("2017-05-22 12:34:56"),
                ],
                [
                    "Key" => "test-project-1/backup-file-3.sql.gz",
                    "Size" => 1000000000,
                    "LastModified" => new \DateTime("2012-01-15 12:34:56"),
                ],
            ]);

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $this->assertEquals(
            "backup-file-2.sql.gz",
            $service->getLatestFile("test-project-1"),
            "The method did not return the expected file name."
        );
    }

    /**
     * Test uploading a file to S3.
     *
     * @test
     */
    public function testUpload()
    {
        $project = "test-project-1";
        $filename = "backup-file-1.sql.gz";
        $dir = "/tmp/test";
        $bucket = "test-bucket";
        $expected_result = "http://example.com/s3/test-bucket/test-project-1/backup-file-1.sql.gz";

        $app = new Application();

        $client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(["putObject"])
            ->getMock();
        $client
            ->expects($this->once())
            ->method("putObject")
            ->with($this->equalTo([
                "Bucket" => $bucket,
                "Key" => sprintf("%s/%s", $project, $filename),
                "SourceFile" => sprintf("%s/%s", $dir, $filename),
            ]))
            ->willReturn([
                "ObjectURL" => $expected_result
            ]);

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $this->assertEquals(
            $expected_result,
            $service->upload($project, sprintf("%s/%s", $dir, $filename))
        );
    }

    /**
     * Test downloading a file from S3.
     *
     * @test
     */
    public function testDownload()
    {
        $project = "test-project-1";
        $filename = "backup-file-1.sql.gz";
        $tmp_dir = "/tmp/test";
        $bucket = "test-bucket";
        $result = sprintf("%s/%s", $tmp_dir, $filename);

        $app = new Application();

        $tmpDirConfig = new Application\Config([
            'tmp_dir' => $tmp_dir
        ]);

        $this->config->merge($tmpDirConfig);

        $client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(["getObject"])
            ->getMock();

        $client
            ->expects($this->once())
            ->method("getObject")
            ->with($this->equalTo([
                "Bucket" => $bucket,
                "Key" => sprintf("%s/%s", $project, $filename),
                "SaveAs" => $result,
            ]));

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $this->assertEquals(
            $result,
            $service->download($project, $filename)
        );
    }

    /**
     * Test deleting a file from S3.
     *
     * @test
     */
    public function testDelete()
    {
        $project = "test-project-1";
        $filename = "backup-file-1.sql.gz";
        $bucket = "test-bucket";

        $app = new Application();

        $client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(["deleteObject"])
            ->getMock();
        $client
            ->expects($this->once())
            ->method("deleteObject")
            ->with($this->equalTo([
                "Bucket" => $bucket,
                "Key" => sprintf("%s/%s", $project, $filename),
            ]));

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $service->delete($project, $filename);
    }

    /**
     * Test deleting old files from S3.
     *
     * @test
     */
    public function testClean()
    {
        $project = "test-project-1";
        $keep = 3;
        $bucket = "test-bucket";
        $files = [
            [
                "Key" => "test-project-1/backup-file-1.sql.gz",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("1999-01-01"),
            ],
            [
                "Key" => "test-project-1/backup-file-2.sql.gz",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("2017-01-01"),
            ],
            [
                "Key" => "test-project-1/backup-file-3.sql.gz",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("2016-01-01"),
            ],
            [
                "Key" => "test-project-1/backup-file-4.sql.gz",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("2001-01-01"),
            ],
            [
                "Key" => "",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("2002-01-01"),
            ],
            [
                "Key" => "test-project-1/backup-file-6.sql.gz",
                "Size" => 1000000000,
                "LastModified" => new \DateTime("2004-01-01"),
            ],
        ];
        $expected_query = [
            "Bucket" => $bucket,
            "Delete" => [
                "Objects" => [
                    ["Key" => "test-project-1/backup-file-1.sql.gz"],
                    ["Key" => "test-project-1/backup-file-4.sql.gz"],
                ],
            ],
        ];

        $app = new Application();

        $client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(["getIterator", "deleteObjects"])
            ->getMock();
        $client
            ->method("getIterator")
            ->willReturn($files);
        $client
            ->expects($this->once())
            ->method("deleteObjects")
            ->with($this->equalTo($expected_query));

        $service = new DigitalOceanSpaces($app, $this->config, $client);

        $service->clean($project, $keep);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Application\ConfigInterface
     */
    protected function getConfigMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(Application\ConfigInterface::class);
    }
}
