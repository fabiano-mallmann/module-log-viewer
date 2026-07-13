<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Block\Adminhtml\Role\Tab;

use Fsm\LogViewer\Api\RoleRuleRepositoryInterface;
use Fsm\LogViewer\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Phrase;

class LogViewer extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Fsm_LogViewer::role/tab/logviewer.phtml';

    /**
     * @param Context $context
     * @param RoleRuleRepositoryInterface $roleRuleRepository
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly RoleRuleRepositoryInterface $roleRuleRepository,
        private readonly AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel(): Phrase
    {
        return __('Log Viewer');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle(): Phrase
    {
        return __('Log Viewer');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab(): bool
    {
        return $this->authorization->isAllowed(Config::ACL_VIEW);
    }

    /**
     * @inheritdoc
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * Role id from the User Role edit request (param rid).
     *
     * @return int
     */
    public function getRoleId(): int
    {
        return (int)$this->getRequest()->getParam('rid');
    }

    /**
     * Patterns configured for the role being edited.
     *
     * @return string
     */
    public function getPatterns(): string
    {
        $roleId = $this->getRoleId();
        if (!$roleId) {
            return '';
        }
        return $this->roleRuleRepository->getByRoleIdOrEmpty($roleId)->getPatterns();
    }

    /**
     * Download flag for the role being edited.
     *
     * @return bool
     */
    public function getAllowDownload(): bool
    {
        $roleId = $this->getRoleId();
        if (!$roleId) {
            return false;
        }
        return $this->roleRuleRepository->getByRoleIdOrEmpty($roleId)->getAllowDownload();
    }

    /**
     * POST field name for patterns.
     *
     * @return string
     */
    public function getPatternsFieldName(): string
    {
        return Config::POST_PATTERNS;
    }

    /**
     * POST field name for allow download.
     *
     * @return string
     */
    public function getAllowDownloadFieldName(): string
    {
        return Config::POST_ALLOW_DOWNLOAD;
    }
}
