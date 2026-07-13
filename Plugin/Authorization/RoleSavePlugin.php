<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Plugin\Authorization;

use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Model\Config;
use Magento\Authorization\Model\Role;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class RoleSavePlugin
{
    /**
     * @param RequestInterface $request
     * @param RoleRuleRepositoryInterface $roleRuleRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RoleRuleRepositoryInterface $roleRuleRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Persist Log Viewer patterns when an admin role is saved.
     *
     * @param Role $subject
     * @param Role $result
     * @return Role
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Role $subject, Role $result): Role
    {
        $roleId = (int)$result->getId();
        if (!$roleId) {
            return $result;
        }

        $post = $this->request->getPostValue();
        if (!is_array($post) || !array_key_exists(Config::POST_PATTERNS, $post)) {
            return $result;
        }

        try {
            $patterns = (string)($post[Config::POST_PATTERNS] ?? '');
            $allowDownload = (bool)(int)($post[Config::POST_ALLOW_DOWNLOAD] ?? 0);

            $rule = $this->roleRuleRepository->getByRoleIdOrEmpty($roleId);
            $rule->setRoleId($roleId);
            $rule->setPatterns($patterns);
            $rule->setAllowDownload($allowDownload);
            $this->roleRuleRepository->save($rule);
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Fsm_LogViewer: failed saving role rule: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to save Log Viewer role rules.'), $e);
        }

        return $result;
    }
}
