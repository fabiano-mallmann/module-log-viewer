<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\ViewModel;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Model\FileSizeFormatter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * View model for the log file contents page.
 */
class LogView implements ArgumentInterface
{
    /**
     * Cached payload; false means not loaded yet.
     *
     * @var array|bool|null
     */
    private $payload = false;

    /**
     * @param LogFileServiceInterface $logFileService
     * @param FileSizeFormatter $fileSizeFormatter
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly LogFileServiceInterface $logFileService,
        private readonly FileSizeFormatter $fileSizeFormatter,
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Requested log file basename.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return (string)$this->request->getParam('file');
    }

    /**
     * Tail payload for the template, or null when unreadable.
     *
     * @return array{name: string, content: string, truncated: bool, size: int}|null
     */
    public function getPayload(): ?array
    {
        if ($this->payload !== false) {
            return $this->payload;
        }
        try {
            $this->payload = $this->logFileService->readTail($this->getFileName());
        } catch (LocalizedException $e) {
            $this->payload = null;
        }
        return $this->payload;
    }

    /**
     * Whether download is allowed for the current admin.
     *
     * @return bool
     */
    public function canDownload(): bool
    {
        return $this->logFileService->canDownload();
    }

    /**
     * URL back to the log listing.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->urlBuilder->getUrl('fsm_log_viewer/log/index');
    }

    /**
     * Download URL for the current file.
     *
     * @return string
     */
    public function getDownloadUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'fsm_log_viewer/log/download',
            ['file' => $this->getFileName()]
        );
    }

    /**
     * Format a byte size for display.
     *
     * @param int $bytes
     * @return string
     */
    public function formatSize(int $bytes): string
    {
        return $this->fileSizeFormatter->format($bytes);
    }
}
