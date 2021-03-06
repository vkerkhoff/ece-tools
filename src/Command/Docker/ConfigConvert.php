<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Docker\Config\Converter;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Converts raw array configuration to .env files.
 */
class ConfigConvert extends Command
{
    const NAME = 'docker:config:convert';

    /**
     * @var array Map of configuration files.
     */
    private static $map = [
        '/docker/config.php' => '/docker/config.env',
    ];

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @param SystemList $directoryList
     * @param File $file
     * @param Converter $converter
     */
    public function __construct(SystemList $directoryList, File $file, Converter $converter)
    {
        $this->systemList = $directoryList;
        $this->file = $file;
        $this->converter = $converter;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Convert raw config to .env files configuration');
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (self::$map as $sourcePath => $envPath) {
            $sourcePath = $this->systemList->getMagentoRoot() . $sourcePath;
            $envPath = $this->systemList->getMagentoRoot() . $envPath;

            if (!$this->file->isExists($sourcePath)) {
                $sourcePath .= '.dist';
            }

            if (!$this->file->isExists($sourcePath)) {
                throw new FileSystemException(sprintf(
                    'Source file %s does not exists',
                    $sourcePath
                ));
            }

            if ($this->file->isExists($envPath)) {
                $this->file->deleteFile($envPath);
            }

            $content = $this->converter->convert(
                $this->file->requireFile($sourcePath)
            );

            $content = implode(PHP_EOL, $content);

            $this->file->filePutContents($envPath, $content);
        }

        $output->writeln('<info>Configuration was converted.</info>');
    }
}
