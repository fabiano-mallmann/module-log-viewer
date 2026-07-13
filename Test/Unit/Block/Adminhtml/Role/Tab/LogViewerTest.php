<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Test\Unit\Block\Adminhtml\Role\Tab;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Block\Adminhtml\Role\Tab\LogViewer;
use Fsm\LogViewer\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LogViewerTest extends TestCase
{
    private RequestInterface|MockObject $request;
    private RoleRuleRepositoryInterface|MockObject $repository;
    private AuthorizationInterface|MockObject $authorization;
    private LogViewer|MockObject $block;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->repository = $this->createMock(RoleRuleRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);

        $this->block = $this->getMockBuilder(LogViewer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();
        $this->block->method('getRequest')->willReturn($this->request);

        $reflection = new ReflectionClass(LogViewer::class);
        $repoProp = $reflection->getProperty('roleRuleRepository');
        $repoProp->setValue($this->block, $this->repository);
        $authProp = $reflection->getProperty('authorization');
        $authProp->setValue($this->block, $this->authorization);
    }

    public function testCanShowTabRespectsAcl(): void
    {
        $this->authorization->method('isAllowed')
            ->with(Config::ACL_VIEW)
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($this->block->canShowTab());
        $this->assertFalse($this->block->canShowTab());
    }

    public function testGetRoleIdFromRequest(): void
    {
        $this->request->method('getParam')->with('rid')->willReturn('42');
        $this->assertSame(42, $this->block->getRoleId());
    }

    public function testGetPatternsEmptyWithoutRoleId(): void
    {
        $this->request->method('getParam')->with('rid')->willReturn(null);
        $this->repository->expects($this->never())->method('getByRoleIdOrEmpty');
        $this->assertSame('', $this->block->getPatterns());
    }

    public function testGetPatternsAndAllowDownloadFromRepository(): void
    {
        $this->request->method('getParam')->with('rid')->willReturn('8');
        $rule = $this->createMock(RoleRuleInterface::class);
        $rule->method('getPatterns')->willReturn("system.log\n");
        $rule->method('getAllowDownload')->willReturn(true);
        $this->repository->method('getByRoleIdOrEmpty')->with(8)->willReturn($rule);

        $this->assertSame("system.log\n", $this->block->getPatterns());
        $this->assertTrue($this->block->getAllowDownload());
    }

    public function testFieldNameHelpers(): void
    {
        $this->assertSame(Config::POST_PATTERNS, $this->block->getPatternsFieldName());
        $this->assertSame(Config::POST_ALLOW_DOWNLOAD, $this->block->getAllowDownloadFieldName());
    }

    public function testIsHiddenAlwaysFalse(): void
    {
        $this->assertFalse($this->block->isHidden());
    }
}
