<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for fsm_log_viewer_role.
 */
class RoleRule extends AbstractDb
{
    public const TABLE_NAME = 'fsm_log_viewer_role';
    public const PRIMARY_KEY = 'role_id';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}
