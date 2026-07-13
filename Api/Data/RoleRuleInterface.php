<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Api\Data;

/**
 * Log Viewer access rule for an admin role.
 *
 * @api
 */
interface RoleRuleInterface
{
    public const ROLE_ID = 'role_id';
    public const PATTERNS = 'patterns';
    public const ALLOW_DOWNLOAD = 'allow_download';

    /**
     * Get admin role id.
     *
     * @return int|null
     */
    public function getRoleId(): ?int;

    /**
     * Set admin role id.
     *
     * @param int $roleId
     * @return $this
     */
    public function setRoleId(int $roleId): self;

    /**
     * Get raw patterns text (one glob per line).
     *
     * @return string
     */
    public function getPatterns(): string;

    /**
     * Set raw patterns text.
     *
     * @param string|null $patterns
     * @return $this
     */
    public function setPatterns(?string $patterns): self;

    /**
     * Whether download is allowed for this role.
     *
     * @return bool
     */
    public function getAllowDownload(): bool;

    /**
     * Set download permission flag.
     *
     * @param bool $allowDownload
     * @return $this
     */
    public function setAllowDownload(bool $allowDownload): self;

    /**
     * Get parsed non-empty glob patterns.
     *
     * @return string[]
     */
    public function getPatternList(): array;
}
