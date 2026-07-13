<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Controller\Adminhtml\Log;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = Config::ACL_VIEW;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LogFileServiceInterface $logFileService
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly LogFileServiceInterface $logFileService
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $fileName = (string)$this->getRequest()->getParam('file');
        try {
            $this->logFileService->assertReadable($fileName);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('*/*/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(Config::ACL_LOGVIEWER);
        $resultPage->getConfig()->getTitle()->prepend(__('View Log: %1', $fileName));
        return $resultPage;
    }
}
