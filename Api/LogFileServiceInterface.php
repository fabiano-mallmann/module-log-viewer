<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Api;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Lists and reads Magento log files allowed for the current admin role.
 *
 * @api
 */
interface LogFileServiceInterface
{
    /**
     * Get current admin ACL role id.
     *
     * @return int
     */
    public function getCurrentRoleId(): int;

    /**
     * Get Log Viewer rule for the current admin role.
     *
     * @return RoleRuleInterface
     */
    public function getCurrentRoleRule(): RoleRuleInterface;

    /**
     * Whether the current admin may download allowed log files.
     *
     * @return bool
     */
    public function canDownload(): bool;

    /**
     * List allowed log files for the current role.
     *
     * @return array<int, array{name: string, size: int, mtime: int}>
     * @throws FileSystemException
     */
    public function listAllowedFiles(): array;

    /**
     * Assert the file is readable for the current role and return its basename.
     *
     * @param string $fileName
     * @return string Safe basename under var/log
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function assertReadable(string $fileName): string;

    /**
     * Read the tail of an allowed log file.
     *
     * @param string $fileName
     * @param int $maxBytes
     * @return array{name: string, content: string, truncated: bool, size: int}
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function readTail(string $fileName, int $maxBytes = 524288): array;

    /**
     * Relative path under var/ for FileFactory, e.g. "log/system.log".
     *
     * @param string $fileName
     * @return string
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function getRelativePathForDownload(string $fileName): string;
}
