<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Test\Unit\Controller\Adminhtml\Log;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Controller\Adminhtml\Log\Download;
use Fsm\LogViewer\Controller\Adminhtml\Log\Index;
use Fsm\LogViewer\Controller\Adminhtml\Log\View;
use Fsm\LogViewer\Model\Config;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ControllersTest extends TestCase
{
    public function testAdminResources(): void
    {
        $this->assertSame(Config::ACL_VIEW, Index::ADMIN_RESOURCE);
        $this->assertSame(Config::ACL_VIEW, View::ADMIN_RESOURCE);
        $this->assertSame(Config::ACL_DOWNLOAD, Download::ADMIN_RESOURCE);
    }

    public function testIndexExecuteBuildsPage(): void
    {
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend');
        $pageConfig = $this->createMock(PageConfig::class);
        $pageConfig->method('getTitle')->willReturn($title);

        $page = $this->createMock(Page::class);
        $page->expects($this->once())->method('setActiveMenu')->with(Config::ACL_LOGVIEWER);
        $page->method('getConfig')->willReturn($pageConfig);

        $pageFactory = $this->createMock(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $controller = new Index($this->createContext(), $pageFactory);
        $this->assertSame($page, $controller->execute());
    }

    public function testViewExecuteRedirectsWhenNotReadable(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('file')->willReturn('system.log');

        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects($this->once())->method('addErrorMessage');

        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->once())->method('setPath')->with('*/*/index')->willReturnSelf();

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirect);

        $logFileService = $this->createMock(LogFileServiceInterface::class);
        $logFileService->method('assertReadable')
            ->willThrowException(new LocalizedException(__('denied')));

        $pageFactory = $this->createMock(PageFactory::class);
        $pageFactory->expects($this->never())->method('create');

        $controller = new View(
            $this->createContext($request, $messageManager, $resultFactory),
            $pageFactory,
            $logFileService
        );
        $this->assertSame($redirect, $controller->execute());
    }

    public function testViewExecuteRendersPageWhenReadable(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('file')->willReturn('system.log');

        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend');
        $pageConfig = $this->createMock(PageConfig::class);
        $pageConfig->method('getTitle')->willReturn($title);

        $page = $this->createMock(Page::class);
        $page->expects($this->once())->method('setActiveMenu')->with(Config::ACL_LOGVIEWER);
        $page->method('getConfig')->willReturn($pageConfig);

        $pageFactory = $this->createMock(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $logFileService = $this->createMock(LogFileServiceInterface::class);
        $logFileService->expects($this->once())->method('assertReadable')->with('system.log');

        $controller = new View(
            $this->createContext($request),
            $pageFactory,
            $logFileService
        );
        $this->assertSame($page, $controller->execute());
    }

    public function testDownloadExecuteReturnsFileResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('file')->willReturn('system.log');

        $response = $this->createMock(ResponseInterface::class);
        $fileFactory = $this->createMock(FileFactory::class);
        $fileFactory->expects($this->once())
            ->method('create')
            ->with(
                'system.log',
                ['type' => 'filename', 'value' => 'log/system.log'],
                'var'
            )
            ->willReturn($response);

        $logFileService = $this->createMock(LogFileServiceInterface::class);
        $logFileService->method('getRelativePathForDownload')
            ->with('system.log')
            ->willReturn('log/system.log');

        $controller = new Download(
            $this->createContext($request),
            $logFileService,
            $fileFactory
        );
        $this->assertSame($response, $controller->execute());
    }

    public function testDownloadExecuteRedirectsOnError(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('file')->willReturn('system.log');

        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects($this->once())->method('addErrorMessage');

        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->once())->method('setPath')->with('*/*/index')->willReturnSelf();

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturn($redirect);

        $logFileService = $this->createMock(LogFileServiceInterface::class);
        $logFileService->method('getRelativePathForDownload')
            ->willThrowException(new LocalizedException(__('nope')));

        $fileFactory = $this->createMock(FileFactory::class);
        $fileFactory->expects($this->never())->method('create');

        $controller = new Download(
            $this->createContext($request, $messageManager, $resultFactory),
            $logFileService,
            $fileFactory
        );
        $this->assertSame($redirect, $controller->execute());
    }

    private function createContext(
        ?RequestInterface $request = null,
        ?ManagerInterface $messageManager = null,
        ?ResultFactory $resultFactory = null
    ): Context|MockObject {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getRequest')->willReturn(
            $request ?? $this->createMock(RequestInterface::class)
        );
        $context->method('getMessageManager')->willReturn(
            $messageManager ?? $this->createMock(ManagerInterface::class)
        );
        $context->method('getResultFactory')->willReturn(
            $resultFactory ?? $this->createMock(ResultFactory::class)
        );
        $context->method('getResponse')->willReturn($this->createMock(ResponseInterface::class));
        $context->method('getObjectManager')->willReturn(
            $this->createMock(\Magento\Framework\ObjectManagerInterface::class)
        );

        return $context;
    }
}
