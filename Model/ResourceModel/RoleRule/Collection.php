<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Model\ResourceModel\RoleRule;

use Fsm\LogViewer\Model\ResourceModel\RoleRule as RoleRuleResource;
use Fsm\LogViewer\Model\RoleRule;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(RoleRule::class, RoleRuleResource::class);
    }
}
