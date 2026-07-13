<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Model\Config;
use Fsm\LogViewer\Model\LogBasenameValidator;
use Fsm\LogViewer\Model\LogFileService;
use Fsm\LogViewer\Model\PatternMatcher;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\File\ReadInterface as FileReadInterface;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LogFileServiceTest extends TestCase
{
    use MockCreationTrait;

    private Filesystem|MockObject $filesystem;
    private AuthSession|MockObject $authSession;
    private RoleRuleRepositoryInterface|MockObject $roleRuleRepository;
    private AuthorizationInterface|MockObject $authorization;
    private LogFileService $service;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->authSession = $this->createPartialMockWithReflection(AuthSession::class, ['getUser']);
        $this->roleRuleRepository = $this->createMock(RoleRuleRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->service = new LogFileService(
            $this->filesystem,
            $this->authSession,
            $this->roleRuleRepository,
            $this->authorization,
            new LogBasenameValidator(),
            new PatternMatcher()
        );
        $this->tmpDir = sys_get_temp_dir() . '/fsm_logviewer_' . uniqid('', true);
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }
        foreach (scandir($this->tmpDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $this->tmpDir . '/' . $entry;
            if (is_link($path) || is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->tmpDir);
    }

    public function testGetCurrentRoleIdWithoutUser(): void
    {
        $this->authSession->method('getUser')->willReturn(null);
        $this->assertSame(0, $this->service->getCurrentRoleId());
    }

    public function testGetCurrentRoleIdWithUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAclRole')->willReturn('7');
        $this->authSession->method('getUser')->willReturn($user);
        $this->assertSame(7, $this->service->getCurrentRoleId());
    }

    public function testCanDownloadRequiresAclAndRoleFlag(): void
    {
        $this->authorization->method('isAllowed')
            ->with(Config::ACL_DOWNLOAD)
            ->willReturn(true);
        $this->stubRoleRule(5, ['system.log'], true);

        $this->assertTrue($this->service->canDownload());
    }

    public function testCanDownloadFalseWhenAclDenied(): void
    {
        $this->authorization->method('isAllowed')->willReturn(false);
        $this->assertFalse($this->service->canDownload());
    }

    public function testCanDownloadFalseWhenRoleDisallows(): void
    {
        $this->authorization->method('isAllowed')->willReturn(true);
        $this->stubRoleRule(5, ['system.log'], false);
        $this->assertFalse($this->service->canDownload());
    }

    public function testListAllowedFilesReturnsEmptyWhenNoPatterns(): void
    {
        $this->stubRoleRule(5, [], false);
        $this->assertSame([], $this->service->listAllowedFiles());
    }

    public function testListAllowedFilesReturnsEmptyWhenLogDirMissing(): void
    {
        $this->stubRoleRule(5, ['*.log'], false);
        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(false);
        $this->filesystem->method('getDirectoryRead')
            ->with(DirectoryList::LOG)
            ->willReturn($logDir);

        $this->assertSame([], $this->service->listAllowedFiles());
    }

    public function testListAllowedFilesFiltersAndSorts(): void
    {
        $this->stubRoleRule(5, ['*.log'], false);
        $system = $this->touchFile('system.log', 'a');
        $exception = $this->touchFile('exception.log', 'b');
        $debug = $this->touchFile('debug.txt', 'c');

        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('read')->willReturn(['system.log', 'exception.log', 'debug.txt']);
        $logDir->method('isFile')->willReturnCallback(
            static fn(string $name): bool => in_array($name, ['system.log', 'exception.log', 'debug.txt'], true)
        );
        $logDir->method('getAbsolutePath')->willReturnCallback(
            function (string $name) use ($system, $exception, $debug): string {
                return match ($name) {
                    'system.log' => $system,
                    'exception.log' => $exception,
                    'debug.txt' => $debug,
                    default => $this->tmpDir . '/' . $name,
                };
            }
        );
        $logDir->method('stat')->willReturnCallback(
            static fn(string $name): array => [
                'size' => filesize(
                    match ($name) {
                        'system.log' => $system,
                        'exception.log' => $exception,
                        default => $debug,
                    }
                ),
                'mtime' => 1700000000,
            ]
        );
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $files = $this->service->listAllowedFiles();
        $names = array_column($files, 'name');
        $this->assertSame(['exception.log', 'system.log'], $names);
    }

    public function testListAllowedFilesSkipsSymlinks(): void
    {
        $this->stubRoleRule(5, ['*.log'], false);
        $real = $this->touchFile('real.log', 'content');
        $link = $this->tmpDir . '/link.log';
        symlink($real, $link);

        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('read')->willReturn(['link.log']);
        $logDir->method('isFile')->willReturn(true);
        $logDir->method('getAbsolutePath')->with('link.log')->willReturn($link);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $this->assertSame([], $this->service->listAllowedFiles());
    }

    public function testAssertReadableDeniesUnmatchedPattern(): void
    {
        $this->stubRoleRule(5, ['system.log'], false);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You are not allowed to access this log file.');
        $this->service->assertReadable('exception.log');
    }

    public function testAssertReadableDeniesMissingFile(): void
    {
        $this->stubRoleRule(5, ['system.log'], false);
        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('isFile')->with('system.log')->willReturn(false);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Log file not found.');
        $this->service->assertReadable('system.log');
    }

    public function testAssertReadableDeniesSymlink(): void
    {
        $this->stubRoleRule(5, ['link.log'], false);
        $real = $this->touchFile('real.log', 'x');
        $link = $this->tmpDir . '/link.log';
        symlink($real, $link);

        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('isFile')->willReturn(true);
        $logDir->method('getAbsolutePath')->willReturn($link);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid log file path.');
        $this->service->assertReadable('link.log');
    }

    public function testReadTailReturnsFullSmallFile(): void
    {
        $this->stubRoleRule(5, ['system.log'], false);
        $path = $this->touchFile('system.log', "line1\nline2\n");
        $handle = $this->createMock(FileReadInterface::class);
        $handle->expects($this->once())->method('read')->with(12)->willReturn("line1\nline2\n");
        $handle->expects($this->once())->method('close');

        $logDir = $this->mockReadableLogDir('system.log', $path, 12, $handle);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $payload = $this->service->readTail('system.log', 1024);
        $this->assertSame('system.log', $payload['name']);
        $this->assertFalse($payload['truncated']);
        $this->assertSame("line1\nline2\n", $payload['content']);
        $this->assertSame(12, $payload['size']);
    }

    public function testReadTailTruncatesAndDropsPartialFirstLine(): void
    {
        $this->stubRoleRule(5, ['system.log'], false);
        $path = $this->touchFile('system.log', 'ignored');
        $handle = $this->createMock(FileReadInterface::class);
        $handle->expects($this->once())->method('seek')->with(5);
        $handle->expects($this->once())->method('read')->with(10)->willReturn("ial\nline2\n");
        $handle->expects($this->once())->method('close');

        $logDir = $this->mockReadableLogDir('system.log', $path, 15, $handle);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $payload = $this->service->readTail('system.log', 10);
        $this->assertTrue($payload['truncated']);
        $this->assertSame("line2\n", $payload['content']);
    }

    public function testGetRelativePathForDownloadDenied(): void
    {
        $this->authorization->method('isAllowed')->willReturn(false);
        $this->stubRoleRule(5, ['system.log'], true);
        $path = $this->touchFile('system.log', 'x');
        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('isFile')->willReturn(true);
        $logDir->method('getAbsolutePath')->willReturn($path);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You are not allowed to download log files.');
        $this->service->getRelativePathForDownload('system.log');
    }

    public function testGetRelativePathForDownloadSuccess(): void
    {
        $this->authorization->method('isAllowed')->willReturn(true);
        $this->stubRoleRule(5, ['system.log'], true);
        $path = $this->touchFile('system.log', 'x');
        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('isFile')->willReturn(true);
        $logDir->method('getAbsolutePath')->willReturn($path);
        $this->filesystem->method('getDirectoryRead')->willReturn($logDir);

        $this->assertSame(
            DirectoryList::LOG . '/system.log',
            $this->service->getRelativePathForDownload('system.log')
        );
    }

    /**
     * @param string[] $patterns
     */
    private function stubRoleRule(int $roleId, array $patterns, bool $allowDownload): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAclRole')->willReturn((string)$roleId);
        $this->authSession->method('getUser')->willReturn($user);

        $rule = $this->createMock(RoleRuleInterface::class);
        $rule->method('getPatternList')->willReturn($patterns);
        $rule->method('getAllowDownload')->willReturn($allowDownload);
        $this->roleRuleRepository->method('getByRoleIdOrEmpty')
            ->with($roleId)
            ->willReturn($rule);
    }

    private function touchFile(string $name, string $content): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    private function mockReadableLogDir(
        string $name,
        string $absolute,
        int $size,
        FileReadInterface $handle
    ): ReadInterface|MockObject {
        $logDir = $this->createMock(ReadInterface::class);
        $logDir->method('isExist')->willReturn(true);
        $logDir->method('isDirectory')->willReturn(true);
        $logDir->method('isFile')->with($name)->willReturn(true);
        $logDir->method('getAbsolutePath')->with($name)->willReturn($absolute);
        $logDir->method('stat')->with($name)->willReturn(['size' => $size, 'mtime' => 1]);
        $logDir->method('openFile')->with($name)->willReturn($handle);
        return $logDir;
    }
}
