<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

/**
 * Matches log basenames against glob patterns (case-insensitive).
 */
class PatternMatcher
{
    /**
     * Return true when the basename matches any of the given globs.
     *
     * @param string $basename Log file basename
     * @param string[] $patterns Glob patterns
     * @return bool
     */
    public function matches(string $basename, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $basename, FNM_CASEFOLD)) {
                return true;
            }
        }
        return false;
    }
}
