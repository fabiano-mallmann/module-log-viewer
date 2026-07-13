<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Validates log basenames requested from the admin UI.
 */
class LogBasenameValidator
{
    /**
     * Whether the name is a safe single-segment basename.
     *
     * @param string $name Candidate basename
     * @return bool
     */
    public function isSafe(string $name): bool
    {
        if ($name === '' || $name === '.' || $name === '..') {
            return false;
        }
        if (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            return false;
        }
        return true;
    }

    /**
     * Normalize a request value to a safe log basename.
     *
     * @param string $fileName Raw request value
     * @return string Safe basename
     * @throws LocalizedException
     */
    public function normalize(string $fileName): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $fileName = basename(str_replace(["\0", '\\'], '', $fileName));
        if (!$this->isSafe($fileName)) {
            throw new LocalizedException(__('Invalid log file name.'));
        }
        return $fileName;
    }
}
