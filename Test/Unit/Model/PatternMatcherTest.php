<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Model\PatternMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PatternMatcherTest extends TestCase
{
    private PatternMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PatternMatcher();
    }

    /**
     * @param string[] $patterns
     */
    #[DataProvider('matchProvider')]
    public function testMatches(string $basename, array $patterns, bool $expected): void
    {
        $this->assertSame($expected, $this->matcher->matches($basename, $patterns));
    }

    /**
     * @return array<string, array{string, string[], bool}>
     */
    public static function matchProvider(): array
    {
        return [
            'exact' => ['system.log', ['system.log'], true],
            'glob' => ['exception.log', ['exception*.log'], true],
            'case insensitive' => ['System.LOG', ['system.log'], true],
            'no match' => ['debug.log', ['system.log', 'exception.log'], false],
            'empty patterns' => ['system.log', [], false],
            'star log' => ['anything.log', ['*.log'], true],
        ];
    }
}
