<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Model\ResourceModel\RoleRule as RoleRuleResource;
use Fsm\LogViewer\Model\RoleRule;
use Fsm\LogViewer\Model\RoleRuleFactory;
use Fsm\LogViewer\Model\RoleRuleRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleRuleRepositoryTest extends TestCase
{
    private RoleRuleFactory|MockObject $factory;
    private RoleRuleResource|MockObject $resource;
    private RoleRuleRepository $repository;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(RoleRuleFactory::class);
        $this->resource = $this->createMock(RoleRuleResource::class);
        $this->repository = new RoleRuleRepository($this->factory, $this->resource);
    }

    public function testGetByRoleIdThrowsWhenMissing(): void
    {
        $rule = $this->createRuleMock();
        $rule->method('getRoleId')->willReturn(null);
        $this->factory->method('create')->willReturn($rule);
        $this->resource->expects($this->once())->method('load')->with($rule, 9);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByRoleId(9);
    }

    public function testGetByRoleIdReturnsExisting(): void
    {
        $rule = $this->createRuleMock();
        $rule->method('getRoleId')->willReturn(9);
        $this->factory->method('create')->willReturn($rule);
        $this->resource->expects($this->once())->method('load')->with($rule, 9);

        $this->assertSame($rule, $this->repository->getByRoleId(9));
    }

    public function testGetByRoleIdOrEmptyCreatesDefaults(): void
    {
        $rule = $this->createRuleMock();
        $rule->method('getRoleId')->willReturnOnConsecutiveCalls(null, 3);
        $rule->expects($this->once())->method('isObjectNew')->with(true);
        $rule->expects($this->once())->method('setRoleId')->with(3)->willReturnSelf();
        $rule->expects($this->once())->method('setPatterns')->with('')->willReturnSelf();
        $rule->expects($this->once())->method('setAllowDownload')->with(false)->willReturnSelf();

        $this->factory->method('create')->willReturn($rule);
        $this->resource->expects($this->once())->method('load')->with($rule, 3);

        $this->assertSame($rule, $this->repository->getByRoleIdOrEmpty(3));
    }

    public function testSavePersistsViaResource(): void
    {
        $rule = $this->createRuleMock();
        $this->resource->expects($this->once())->method('save')->with($rule);
        $this->assertSame($rule, $this->repository->save($rule));
    }

    public function testDeleteByRoleIdDeletesWhenPresent(): void
    {
        $rule = $this->createRuleMock();
        $rule->method('getRoleId')->willReturn(4);
        $this->factory->method('create')->willReturn($rule);
        $this->resource->expects($this->once())->method('load')->with($rule, 4);
        $this->resource->expects($this->once())->method('delete')->with($rule);

        $this->repository->deleteByRoleId(4);
    }

    public function testDeleteByRoleIdSkipsWhenMissing(): void
    {
        $rule = $this->createRuleMock();
        $rule->method('getRoleId')->willReturn(null);
        $this->factory->method('create')->willReturn($rule);
        $this->resource->expects($this->once())->method('load')->with($rule, 4);
        $this->resource->expects($this->never())->method('delete');

        $this->repository->deleteByRoleId(4);
    }

    private function createRuleMock(): RoleRule|MockObject
    {
        return $this->getMockBuilder(RoleRule::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getRoleId',
                'isObjectNew',
                'setRoleId',
                'setPatterns',
                'setAllowDownload',
            ])
            ->getMock();
    }
}
