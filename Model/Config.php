<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

/**
 * Shared ACL and form field identifiers for the Log Viewer module.
 */
class Config
{
    public const ACL_LOGVIEWER = 'Fsm_LogViewer::logviewer';
    public const ACL_VIEW = 'Fsm_LogViewer::view';
    public const ACL_DOWNLOAD = 'Fsm_LogViewer::download';

    public const POST_PATTERNS = 'logviewer_patterns';
    public const POST_ALLOW_DOWNLOAD = 'logviewer_allow_download';

    public const BYTES_PER_KB = 1024;
    public const BYTES_PER_MB = 1048576;
}
