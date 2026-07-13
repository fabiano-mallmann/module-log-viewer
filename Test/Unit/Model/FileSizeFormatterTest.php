<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Model;

use Fsm\LogViewer\Model\FileSizeFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileSizeFormatterTest extends TestCase
{
    private FileSizeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new FileSizeFormatter();
    }

    #[DataProvider('formatProvider')]
    public function testFormat(int $bytes, string $expected): void
    {
        $this->assertSame($expected, $this->formatter->format($bytes));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function formatProvider(): array
    {
        return [
            'bytes' => [500, '500 B'],
            'kilobytes' => [2048, '2 KB'],
            'megabytes' => [2097152, '2 MB'],
        ];
    }
}
