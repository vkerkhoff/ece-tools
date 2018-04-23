<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityManager;
use Psr\Log\LoggerInterface;

/**
 * Utility class for static content compression.
 */
class StaticContentCompressor
{
    /**
     * Target directory to be compressed relative to the Magento application folder.
     */
    const TARGET_DIR = 'pub/static';

    /**
     * Default gzip compression level if not otherwise specified.
     *
     * Compression level 4 takes about as long as compression level 1.
     * It's just as fast because the reduction in I/O from the smaller
     * compressed file speeds up compression about as fast as the increased
     * CPU usage slows it down.
     * Compression level 4 is the default instead of compression level 1 as a
     * result.
     */
    const DEFAULT_COMPRESSION_LEVEL = 4;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var UtilityManager
     */
    private $utilityManager;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param UtilityManager $utilityManager
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        UtilityManager $utilityManager
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->utilityManager = $utilityManager;
    }

    /**
     * Compress select files in the static content directory.
     *
     * @param int $compressionLevel
     * @param string $verbose
     * @return void
     */
    public function process(int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL, string $verbose = '')
    {
        if ($compressionLevel === 0) {
            $this->logger->info('Static content compression was disabled.');

            return;
        }

        $compressionCommand = $this->getCompressionCommand($compressionLevel);

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        if ($verbose) {
            $this->logger->info(
                "Static content compression took $duration seconds.",
                [
                    'commandRun' => $compressionCommand,
                ]
            );
        }
    }

    /**
     * Return the inner find/xargs/gzip command that compresses the content.
     *
     * @return string
     */
    private function innerCompressionCommand(): string
    {
        return "find " . escapeshellarg(static::TARGET_DIR) . " -type f -size +300c"
            . " '(' -name '*.js' -or -name '*.css' -or -name '*.svg'"
            . " -or -name '*.html' -or -name '*.htm' ')' -print0"
            . " | xargs -0 -n100 -P16 gzip -q --keep";
    }

    /**
     * Get the string containing the full shell command for compression.
     *
     * @param int $compressionLevel
     * @return string
     */
    private function getCompressionCommand(
        int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL
    ): string {
        $compressionLevel = (int)$compressionLevel;
        $compressionLevel = $compressionLevel > 0 && $compressionLevel <= 9
            ? $compressionLevel
            : static::DEFAULT_COMPRESSION_LEVEL;

        return sprintf(
            '%s -k 30 600 %s -c "%s -%s"',
            $this->utilityManager->get(UtilityManager::UTILITY_TIMEOUT),
            $this->utilityManager->get(UtilityManager::UTILITY_BASH),
            $this->innerCompressionCommand(),
            $compressionLevel
        );
    }
}
