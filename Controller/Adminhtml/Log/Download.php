<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Controller\Adminhtml\Log;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Download extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = Config::ACL_DOWNLOAD;

    /**
     * @param Context $context
     * @param LogFileServiceInterface $logFileService
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        private readonly LogFileServiceInterface $logFileService,
        private readonly FileFactory $fileFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $fileName = (string)$this->getRequest()->getParam('file');
        try {
            $relative = $this->logFileService->getRelativePathForDownload($fileName);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $downloadName = basename($relative);
            return $this->fileFactory->create(
                $downloadName,
                ['type' => 'filename', 'value' => $relative],
                DirectoryList::VAR_DIR
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('*/*/index');
        }
    }
}
