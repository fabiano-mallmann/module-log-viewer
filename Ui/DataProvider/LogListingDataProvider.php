<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Ui\DataProvider;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Filesystem-backed listing of allowed log files (not a DB collection).
 */
class LogListingDataProvider extends AbstractDataProvider implements DataProviderInterface
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param LogFileServiceInterface $logFileService
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly LogFileServiceInterface $logFileService,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        $items = $this->logFileService->listAllowedFiles();
        $items = $this->applyFilters($items);
        $items = $this->applySorting($items);

        $total = count($items);
        $items = $this->applyPaging($items);

        foreach ($items as &$item) {
            $item['id'] = $item['name'];
            // UTC datetime string; Magento\Ui Date column applies store timezone once.
            $item['mtime'] = gmdate('Y-m-d H:i:s', (int)$item['mtime']);
        }
        unset($item);

        return [
            'totalRecords' => $total,
            'items' => array_values($items),
        ];
    }

    /**
     * Filters are applied in getData() from the request (filesystem listing).
     *
     * @param Filter $filter
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function addFilter(Filter $filter)
    {
    }

    /**
     * Sorting is applied in getData() from the request.
     *
     * @param string $field
     * @param string $direction
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function addOrder($field, $direction)
    {
    }

    /**
     * Paging is applied in getData() from the request.
     *
     * @param int $offset
     * @param int $size
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function setLimit($offset, $size)
    {
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        return null;
    }

    /**
     * Apply listing filters from the UI request.
     *
     * @param array $items
     * @return array
     */
    private function applyFilters(array $items): array
    {
        $filters = (array)$this->request->getParam('filters', []);
        $search = trim((string)$this->request->getParam('search', ''));

        if ($search !== '') {
            $items = array_values(array_filter(
                $items,
                static fn(array $item): bool => stripos($item['name'], $search) !== false
            ));
        }

        if (isset($filters['name']) && is_string($filters['name']) && $filters['name'] !== '') {
            $needle = $filters['name'];
            $items = array_values(array_filter(
                $items,
                static fn(array $item): bool => stripos($item['name'], $needle) !== false
            ));
        }

        if (isset($filters['size']) && is_array($filters['size'])) {
            $from = isset($filters['size']['from']) && $filters['size']['from'] !== ''
                ? (int)$filters['size']['from']
                : null;
            $to = isset($filters['size']['to']) && $filters['size']['to'] !== ''
                ? (int)$filters['size']['to']
                : null;
            $items = array_values(array_filter(
                $items,
                static function (array $item) use ($from, $to): bool {
                    if ($from !== null && $item['size'] < $from) {
                        return false;
                    }
                    if ($to !== null && $item['size'] > $to) {
                        return false;
                    }
                    return true;
                }
            ));
        }

        if (isset($filters['mtime']) && is_array($filters['mtime'])) {
            $fromTs = $this->parseFilterDate($filters['mtime']['from'] ?? null, false);
            $toTs = $this->parseFilterDate($filters['mtime']['to'] ?? null, true);
            $items = array_values(array_filter(
                $items,
                static function (array $item) use ($fromTs, $toTs): bool {
                    if ($fromTs !== null && $item['mtime'] < $fromTs) {
                        return false;
                    }
                    if ($toTs !== null && $item['mtime'] > $toTs) {
                        return false;
                    }
                    return true;
                }
            ));
        }

        return $items;
    }

    /**
     * Apply listing sort from the UI request.
     *
     * @param array $items
     * @return array
     */
    private function applySorting(array $items): array
    {
        $sorting = (array)$this->request->getParam('sorting', []);
        $field = (string)($sorting['field'] ?? 'name');
        $direction = strtolower((string)($sorting['direction'] ?? 'asc')) === 'desc' ? -1 : 1;

        if (!in_array($field, ['name', 'size', 'mtime'], true)) {
            $field = 'name';
        }

        usort(
            $items,
            static function (array $a, array $b) use ($field, $direction): int {
                if ($field === 'name') {
                    return $direction * strcmp($a['name'], $b['name']);
                }
                return $direction * ($a[$field] <=> $b[$field]);
            }
        );

        return $items;
    }

    /**
     * Apply listing paging from the UI request.
     *
     * @param array $items
     * @return array
     */
    private function applyPaging(array $items): array
    {
        $paging = (array)$this->request->getParam('paging', []);
        $pageSize = max(1, (int)($paging['pageSize'] ?? 20));
        $current = max(1, (int)($paging['current'] ?? 1));
        $offset = ($current - 1) * $pageSize;

        return array_slice($items, $offset, $pageSize);
    }

    /**
     * Parse a date filter value into a unix timestamp.
     *
     * @param mixed $value
     * @param bool $endOfDay
     * @return int|null
     */
    private function parseFilterDate(mixed $value, bool $endOfDay): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return null;
        }
        if ($endOfDay) {
            return strtotime(date('Y-m-d 23:59:59', $timestamp)) ?: $timestamp;
        }
        return strtotime(date('Y-m-d 00:00:00', $timestamp)) ?: $timestamp;
    }
}
