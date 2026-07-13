<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Plugin\Authorization;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Model\Config;
use Fsm\LogViewer\Plugin\Authorization\RoleSavePlugin;
use Magento\Authorization\Model\Role;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class RoleSavePluginTest extends TestCase
{
    use MockCreationTrait;

    private HttpRequest|MockObject $request;
    private RoleRuleRepositoryInterface|MockObject $repository;
    private LoggerInterface|MockObject $logger;
    private RoleSavePlugin $plugin;

    protected function setUp(): void
    {
        $this->request = $this->createPartialMockWithReflection(HttpRequest::class, ['getPostValue']);
        $this->repository = $this->createMock(RoleRuleRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new RoleSavePlugin($this->request, $this->repository, $this->logger);
    }

    public function testAfterSaveSkipsWhenRoleHasNoId(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        $this->assertSame($role, $this->plugin->afterSave($role, $role));
    }

    public function testAfterSaveSkipsWhenPatternsFieldMissing(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(10);
        $this->request->method('getPostValue')->willReturn(['role_name' => 'Admin']);
        $this->repository->expects($this->never())->method('save');

        $this->assertSame($role, $this->plugin->afterSave($role, $role));
    }

    public function testAfterSavePersistsRule(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(10);
        $this->request->method('getPostValue')->willReturn([
            Config::POST_PATTERNS => "system.log\nexception.log",
            Config::POST_ALLOW_DOWNLOAD => '1',
        ]);

        $rule = $this->createMock(RoleRuleInterface::class);
        $rule->expects($this->once())->method('setRoleId')->with(10)->willReturnSelf();
        $rule->expects($this->once())
            ->method('setPatterns')
            ->with("system.log\nexception.log")
            ->willReturnSelf();
        $rule->expects($this->once())->method('setAllowDownload')->with(true)->willReturnSelf();

        $this->repository->expects($this->once())
            ->method('getByRoleIdOrEmpty')
            ->with(10)
            ->willReturn($rule);
        $this->repository->expects($this->once())->method('save')->with($rule)->willReturn($rule);

        $this->assertSame($role, $this->plugin->afterSave($role, $role));
    }

    public function testAfterSaveRethrowsLocalizedException(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(10);
        $this->request->method('getPostValue')->willReturn([
            Config::POST_PATTERNS => 'system.log',
        ]);

        $rule = $this->createMock(RoleRuleInterface::class);
        $rule->method('setRoleId')->willReturnSelf();
        $rule->method('setPatterns')->willReturnSelf();
        $rule->method('setAllowDownload')->willReturnSelf();
        $this->repository->method('getByRoleIdOrEmpty')->willReturn($rule);
        $this->repository->method('save')->willThrowException(
            new LocalizedException(__('Already localized'))
        );
        $this->logger->expects($this->never())->method('error');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Already localized');
        $this->plugin->afterSave($role, $role);
    }

    public function testAfterSaveWrapsUnexpectedThrowable(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(10);
        $this->request->method('getPostValue')->willReturn([
            Config::POST_PATTERNS => 'system.log',
        ]);

        $rule = $this->createMock(RoleRuleInterface::class);
        $rule->method('setRoleId')->willReturnSelf();
        $rule->method('setPatterns')->willReturnSelf();
        $rule->method('setAllowDownload')->willReturnSelf();
        $this->repository->method('getByRoleIdOrEmpty')->willReturn($rule);
        $this->repository->method('save')->willThrowException(new RuntimeException('db down'));
        $this->logger->expects($this->once())->method('error');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Unable to save Log Viewer role rules.');
        $this->plugin->afterSave($role, $role);
    }
}
