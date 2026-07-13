<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Model\LogBasenameValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LogBasenameValidatorTest extends TestCase
{
    private LogBasenameValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LogBasenameValidator();
    }

    #[DataProvider('safeProvider')]
    public function testIsSafe(string $name, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isSafe($name));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function safeProvider(): array
    {
        return [
            'simple' => ['system.log', true],
            'empty' => ['', false],
            'dot' => ['.', false],
            'dotdot' => ['..', false],
            'slash' => ['../system.log', false],
            'backslash' => ['foo\\bar.log', false],
            'null byte' => ["system.log\0", false],
        ];
    }

    public function testNormalizeStripsDirectoryComponents(): void
    {
        $this->assertSame('system.log', $this->validator->normalize('../system.log'));
    }

    public function testNormalizeRejectsUnsafeAfterBasename(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->normalize('..');
    }
}
