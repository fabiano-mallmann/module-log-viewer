<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for {@see RoleRule}.
 */
class RoleRuleFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = RoleRule::class
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data = []): RoleRule
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
