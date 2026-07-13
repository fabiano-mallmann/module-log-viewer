<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Api;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Persists Log Viewer rules per admin role.
 *
 * @api
 */
interface RoleRuleRepositoryInterface
{
    /**
     * Load rule by role id.
     *
     * @param int $roleId
     * @return RoleRuleInterface
     * @throws NoSuchEntityException
     */
    public function getByRoleId(int $roleId): RoleRuleInterface;

    /**
     * Load rule or return a new empty instance bound to the role.
     *
     * @param int $roleId
     * @return RoleRuleInterface
     */
    public function getByRoleIdOrEmpty(int $roleId): RoleRuleInterface;

    /**
     * Persist a role rule.
     *
     * @param RoleRuleInterface $rule
     * @return RoleRuleInterface
     */
    public function save(RoleRuleInterface $rule): RoleRuleInterface;

    /**
     * Delete rule for a role id when present.
     *
     * @param int $roleId
     * @return void
     */
    public function deleteByRoleId(int $roleId): void;
}
