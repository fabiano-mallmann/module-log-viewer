<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Model;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * Lists and reads Magento log files allowed for the current admin role.
 */
class LogFileService implements LogFileServiceInterface
{
    private const MAX_VIEW_BYTES = 524288;

    /**
     * @param Filesystem $filesystem
     * @param AuthSession $authSession
     * @param RoleRuleRepositoryInterface $roleRuleRepository
     * @param AuthorizationInterface $authorization
     * @param LogBasenameValidator $basenameValidator
     * @param PatternMatcher $patternMatcher
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AuthSession $authSession,
        private readonly RoleRuleRepositoryInterface $roleRuleRepository,
        private readonly AuthorizationInterface $authorization,
        private readonly LogBasenameValidator $basenameValidator,
        private readonly PatternMatcher $patternMatcher
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getCurrentRoleId(): int
    {
        $user = $this->authSession->getUser();
        if (!$user) {
            return 0;
        }
        return (int)$user->getAclRole();
    }

    /**
     * @inheritdoc
     */
    public function getCurrentRoleRule(): RoleRuleInterface
    {
        $roleId = $this->getCurrentRoleId();
        if (!$roleId) {
            $rule = $this->roleRuleRepository->getByRoleIdOrEmpty(0);
            $rule->setPatterns('');
            $rule->setAllowDownload(false);
            return $rule;
        }
        return $this->roleRuleRepository->getByRoleIdOrEmpty($roleId);
    }

    /**
     * @inheritdoc
     */
    public function canDownload(): bool
    {
        if (!$this->authorization->isAllowed(Config::ACL_DOWNLOAD)) {
            return false;
        }
        return $this->getCurrentRoleRule()->getAllowDownload();
    }

    /**
     * @inheritdoc
     */
    public function listAllowedFiles(): array
    {
        $patterns = $this->getCurrentRoleRule()->getPatternList();
        if ($patterns === []) {
            return [];
        }

        $logDir = $this->getLogDirectory();
        if ($logDir === null) {
            return [];
        }

        $files = [];
        foreach ($logDir->read() as $relativePath) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $entry = basename($relativePath);
            if (!$this->basenameValidator->isSafe($entry)) {
                continue;
            }
            if (!$this->patternMatcher->matches($entry, $patterns)) {
                continue;
            }
            if (!$logDir->isFile($entry)) {
                continue;
            }

            $absolute = $logDir->getAbsolutePath($entry);
            // Magento Filesystem API has no isLink(); skip symlinks for path-safety.
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (is_link($absolute)) {
                continue;
            }

            $stat = $logDir->stat($entry);
            $files[] = [
                'name' => $entry,
                'size' => (int)($stat['size'] ?? 0),
                'mtime' => (int)($stat['mtime'] ?? 0),
            ];
        }

        usort($files, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        return $files;
    }

    /**
     * @inheritdoc
     */
    public function assertReadable(string $fileName): string
    {
        $safeName = $this->basenameValidator->normalize($fileName);
        $patterns = $this->getCurrentRoleRule()->getPatternList();
        if ($patterns === [] || !$this->patternMatcher->matches($safeName, $patterns)) {
            throw new LocalizedException(__('You are not allowed to access this log file.'));
        }

        $logDir = $this->getLogDirectory();
        if ($logDir === null) {
            throw new LocalizedException(__('Log directory is not available.'));
        }

        if (!$logDir->isFile($safeName)) {
            throw new LocalizedException(__('Log file not found.'));
        }

        $absolute = $logDir->getAbsolutePath($safeName);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (is_link($absolute)) {
            throw new LocalizedException(__('Invalid log file path.'));
        }

        return $safeName;
    }

    /**
     * @inheritdoc
     */
    public function readTail(string $fileName, int $maxBytes = self::MAX_VIEW_BYTES): array
    {
        $safeName = $this->assertReadable($fileName);
        $logDir = $this->getLogDirectory();
        if ($logDir === null) {
            throw new LocalizedException(__('Log directory is not available.'));
        }

        $stat = $logDir->stat($safeName);
        $size = (int)($stat['size'] ?? 0);
        $truncated = $size > $maxBytes;
        $offset = $truncated ? ($size - $maxBytes) : 0;

        $handle = $logDir->openFile($safeName);
        try {
            if ($offset > 0) {
                $handle->seek($offset);
            }
            $content = $handle->read($truncated ? $maxBytes : max($size, 1));
            if ($content === false || $content === null) {
                throw new LocalizedException(__('Unable to read log file.'));
            }
            $content = (string)$content;
            if ($truncated) {
                $firstNl = strpos($content, "\n");
                if ($firstNl !== false) {
                    $content = substr($content, $firstNl + 1);
                }
            }
        } finally {
            $handle->close();
        }

        return [
            'name' => $safeName,
            'content' => $content,
            'truncated' => $truncated,
            'size' => $size,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRelativePathForDownload(string $fileName): string
    {
        $safeName = $this->assertReadable($fileName);
        if (!$this->canDownload()) {
            throw new LocalizedException(__('You are not allowed to download log files.'));
        }
        return DirectoryList::LOG . '/' . $safeName;
    }

    /**
     * Resolve the readable Magento log directory, or null when unavailable.
     *
     * @return ReadInterface|null
     * @throws FileSystemException
     */
    private function getLogDirectory(): ?ReadInterface
    {
        $logDir = $this->filesystem->getDirectoryRead(DirectoryList::LOG);
        if (!$logDir->isExist() || !$logDir->isDirectory()) {
            return null;
        }
        return $logDir;
    }
}
