<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Model\RoleRule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RoleRuleTest extends TestCase
{
    public function testGetPatternListParsesLinesAndTrims(): void
    {
        $rule = $this->createRoleRule();
        $rule->setPatterns(" system.log \n\nexception.log\r\ndebug.log ");

        $this->assertSame(
            ['system.log', 'exception.log', 'debug.log'],
            $rule->getPatternList()
        );
    }

    public function testGetPatternListEmpty(): void
    {
        $rule = $this->createRoleRule();
        $rule->setPatterns('');

        $this->assertSame([], $rule->getPatternList());
    }

    public function testAllowDownloadFlagRoundTrip(): void
    {
        $rule = $this->createRoleRule();
        $rule->setAllowDownload(true);
        $this->assertTrue($rule->getAllowDownload());
        $rule->setAllowDownload(false);
        $this->assertFalse($rule->getAllowDownload());
    }

    private function createRoleRule(): RoleRule
    {
        $reflection = new ReflectionClass(RoleRule::class);
        /** @var RoleRule $rule */
        $rule = $reflection->newInstanceWithoutConstructor();
        return $rule;
    }
}
