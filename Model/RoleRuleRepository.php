<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Model\ResourceModel\RoleRule as RoleRuleResource;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Repository for Log Viewer role rules.
 */
class RoleRuleRepository implements RoleRuleRepositoryInterface
{
    /**
     * @param RoleRuleFactory $roleRuleFactory
     * @param RoleRuleResource $roleRuleResource
     */
    public function __construct(
        private readonly RoleRuleFactory $roleRuleFactory,
        private readonly RoleRuleResource $roleRuleResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getByRoleId(int $roleId): RoleRuleInterface
    {
        $rule = $this->roleRuleFactory->create();
        $this->roleRuleResource->load($rule, $roleId);
        if (!$rule->getRoleId()) {
            throw new NoSuchEntityException(
                __('Log Viewer rule for role id %1 does not exist.', $roleId)
            );
        }
        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function getByRoleIdOrEmpty(int $roleId): RoleRuleInterface
    {
        $rule = $this->roleRuleFactory->create();
        $this->roleRuleResource->load($rule, $roleId);
        if (!$rule->getRoleId()) {
            $rule->isObjectNew(true);
            $rule->setRoleId($roleId);
            $rule->setPatterns('');
            $rule->setAllowDownload(false);
        }
        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function save(RoleRuleInterface $rule): RoleRuleInterface
    {
        /** @var RoleRule $rule */
        $this->roleRuleResource->save($rule);
        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function deleteByRoleId(int $roleId): void
    {
        $rule = $this->roleRuleFactory->create();
        $this->roleRuleResource->load($rule, $roleId);
        if ($rule->getRoleId()) {
            $this->roleRuleResource->delete($rule);
        }
    }
}
