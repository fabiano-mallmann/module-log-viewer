<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Test\Unit\Ui\DataProvider;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Fsm\LogViewer\Ui\DataProvider\LogListingDataProvider;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogListingDataProviderTest extends TestCase
{
    private LogFileServiceInterface|MockObject $logFileService;
    private RequestInterface|MockObject $request;

    protected function setUp(): void
    {
        $this->logFileService = $this->createMock(LogFileServiceInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
    }

    public function testGetDataFormatsMtimeAsUtcAndAddsId(): void
    {
        $mtime = 1700000000;
        $this->logFileService->method('listAllowedFiles')->willReturn([
            ['name' => 'system.log', 'size' => 100, 'mtime' => $mtime],
        ]);
        $this->stubRequestParams();

        $data = $this->createProvider()->getData();

        $this->assertSame(1, $data['totalRecords']);
        $this->assertSame('system.log', $data['items'][0]['id']);
        $this->assertSame(gmdate('Y-m-d H:i:s', $mtime), $data['items'][0]['mtime']);
    }

    public function testGetDataFiltersBySearchAndName(): void
    {
        $this->logFileService->method('listAllowedFiles')->willReturn([
            ['name' => 'system.log', 'size' => 10, 'mtime' => 1],
            ['name' => 'exception.log', 'size' => 20, 'mtime' => 2],
            ['name' => 'debug.log', 'size' => 30, 'mtime' => 3],
        ]);
        $this->stubRequestParams(
            filters: ['name' => 'exception'],
            search: 'log'
        );

        $data = $this->createProvider()->getData();
        $this->assertSame(1, $data['totalRecords']);
        $this->assertSame('exception.log', $data['items'][0]['name']);
    }

    public function testGetDataFiltersBySizeRange(): void
    {
        $this->logFileService->method('listAllowedFiles')->willReturn([
            ['name' => 'a.log', 'size' => 10, 'mtime' => 1],
            ['name' => 'b.log', 'size' => 50, 'mtime' => 2],
            ['name' => 'c.log', 'size' => 100, 'mtime' => 3],
        ]);
        $this->stubRequestParams(filters: ['size' => ['from' => '20', 'to' => '80']]);

        $data = $this->createProvider()->getData();
        $this->assertSame(1, $data['totalRecords']);
        $this->assertSame('b.log', $data['items'][0]['name']);
    }

    public function testGetDataSortsDescendingBySize(): void
    {
        $this->logFileService->method('listAllowedFiles')->willReturn([
            ['name' => 'a.log', 'size' => 10, 'mtime' => 1],
            ['name' => 'b.log', 'size' => 50, 'mtime' => 2],
        ]);
        $this->stubRequestParams(sorting: ['field' => 'size', 'direction' => 'desc']);

        $data = $this->createProvider()->getData();
        $this->assertSame(['b.log', 'a.log'], array_column($data['items'], 'name'));
    }

    public function testGetDataPaginates(): void
    {
        $this->logFileService->method('listAllowedFiles')->willReturn([
            ['name' => 'a.log', 'size' => 1, 'mtime' => 1],
            ['name' => 'b.log', 'size' => 2, 'mtime' => 2],
            ['name' => 'c.log', 'size' => 3, 'mtime' => 3],
        ]);
        $this->stubRequestParams(paging: ['pageSize' => 1, 'current' => 2]);

        $data = $this->createProvider()->getData();
        $this->assertSame(3, $data['totalRecords']);
        $this->assertCount(1, $data['items']);
        $this->assertSame('b.log', $data['items'][0]['name']);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $sorting
     * @param array<string, mixed> $paging
     */
    private function stubRequestParams(
        array $filters = [],
        string $search = '',
        array $sorting = [],
        array $paging = []
    ): void {
        $this->request->method('getParam')->willReturnCallback(
            static function (string $key, mixed $default = null) use ($filters, $search, $sorting, $paging): mixed {
                return match ($key) {
                    'filters' => $filters,
                    'search' => $search,
                    'sorting' => $sorting,
                    'paging' => $paging,
                    default => $default,
                };
            }
        );
    }

    private function createProvider(): LogListingDataProvider
    {
        return new LogListingDataProvider(
            'fsm_log_viewer_log_listing_data_source',
            'name',
            'name',
            $this->logFileService,
            $this->request
        );
    }
}
