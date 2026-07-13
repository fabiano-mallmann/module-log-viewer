<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

/**
 * Formats byte sizes for admin UI display.
 */
class FileSizeFormatter
{
    /**
     * Convert a byte count into a short human-readable string.
     *
     * @param int $bytes File size in bytes
     * @return string Human-readable size
     */
    public function format(int $bytes): string
    {
        if ($bytes < Config::BYTES_PER_KB) {
            return $bytes . ' B';
        }
        if ($bytes < Config::BYTES_PER_MB) {
            return round($bytes / Config::BYTES_PER_KB, 1) . ' KB';
        }
        return round($bytes / Config::BYTES_PER_MB, 2) . ' MB';
    }
}
