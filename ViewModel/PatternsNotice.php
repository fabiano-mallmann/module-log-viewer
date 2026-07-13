<?php
declare(strict_types=1);

namespace Fsm\LogViewer\ViewModel;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * View model for the patterns notice above the log listing.
 */
class PatternsNotice implements ArgumentInterface
{
    /**
     * @param LogFileServiceInterface $logFileService
     */
    public function __construct(
        private readonly LogFileServiceInterface $logFileService
    ) {
    }

    /**
     * Comma-separated patterns for the current role.
     *
     * @return string
     */
    public function getPatternsHint(): string
    {
        $patterns = $this->logFileService->getCurrentRoleRule()->getPatternList();
        return $patterns === [] ? '' : implode(', ', $patterns);
    }

    /**
     * Whether the current role has any patterns configured.
     *
     * @return bool
     */
    public function hasPatterns(): bool
    {
        return $this->logFileService->getCurrentRoleRule()->getPatternList() !== [];
    }
}
