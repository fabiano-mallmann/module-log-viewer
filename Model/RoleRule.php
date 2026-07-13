<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Model;

use Fsm\LogViewer\Api\Data\RoleRuleInterface;
use Fsm\LogViewer\Model\ResourceModel\RoleRule as RoleRuleResource;
use Magento\Framework\Model\AbstractModel;

class RoleRule extends AbstractModel implements RoleRuleInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(RoleRuleResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getRoleId(): ?int
    {
        $value = $this->getData(self::ROLE_ID);
        return $value !== null ? (int)$value : null;
    }

    /**
     * @inheritdoc
     */
    public function setRoleId(int $roleId): RoleRuleInterface
    {
        return $this->setData(self::ROLE_ID, $roleId);
    }

    /**
     * @inheritdoc
     */
    public function getPatterns(): string
    {
        return (string)$this->getData(self::PATTERNS);
    }

    /**
     * @inheritdoc
     */
    public function setPatterns(?string $patterns): RoleRuleInterface
    {
        return $this->setData(self::PATTERNS, $patterns);
    }

    /**
     * @inheritdoc
     */
    public function getAllowDownload(): bool
    {
        return (bool)(int)$this->getData(self::ALLOW_DOWNLOAD);
    }

    /**
     * @inheritdoc
     */
    public function setAllowDownload(bool $allowDownload): RoleRuleInterface
    {
        return $this->setData(self::ALLOW_DOWNLOAD, $allowDownload ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getPatternList(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $this->getPatterns()) ?: [];
        $patterns = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $patterns[] = $line;
            }
        }
        return $patterns;
    }
}
