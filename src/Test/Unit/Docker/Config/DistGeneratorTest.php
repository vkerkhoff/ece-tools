<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Config;

use Magento\MagentoCloud\Docker\Config\DistGenerator;
use Magento\MagentoCloud\Docker\Config\Relationship;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\PhpFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DistGeneratorTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Relationship|MockObject
     */
    private $relationshipMock;

    /**
     * @var PhpFormatter|MockObject
     */
    private $phpFormatterMock;

    /**
     * @var DistGenerator
     */
    private $distGenerator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->relationshipMock = $this->createMock(Relationship::class);
        $this->phpFormatterMock = $this->createMock(PhpFormatter::class);

        $this->distGenerator = new DistGenerator(
            $this->directoryListMock,
            $this->fileMock,
            $this->relationshipMock,
            $this->phpFormatterMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testGenerate()
    {
        $rootDir = '/path/to';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->relationshipMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'database' => ['config'],
                'redis' => ['config'],
            ]);
        $this->phpFormatterMock->expects($this->exactly(3))
            ->method('varExportShort')
            ->willReturnMap([
                [
                    [
                        'database' => ['config'],
                        'redis' => ['config'],
                    ],
                    2,
                    'exported_relationship_value',
                ],
                [
                    [
                        'http://magento2.docker/' => [
                            'type' => 'upstream',
                            'original_url' => 'http://{default}'
                        ],
                        'https://magento2.docker/' => [
                            'type' => 'upstream',
                            'original_url' => 'https://{default}'
                        ],
                    ],
                    2,
                    'exported_routes_value',
                ],
                [
                    [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_PASSWORD' => '123123q',
                        'ADMIN_URL' => 'admin'
                    ],
                    2,
                    'exported_variables_value'
                ]
            ]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($rootDir . '/docker/config.php.dist', $this->getConfigForUpdate());

        $this->distGenerator->generate();
    }

    /**
     * @return string
     */
    private function getConfigForUpdate(): string
    {
        return <<<TEXT
<?php

return [
    'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(exported_relationship_value)),
    'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(exported_routes_value)),
    'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(exported_variables_value)),
];

TEXT;
    }

    /**
     * @expectedExceptionMessage file system error
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testGenerateFileSystemException()
    {
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->willThrowException(new FileSystemException('file system error'));

        $this->distGenerator->generate();
    }
}
