<?php
declare(strict_types=1);

namespace Fsm\LogViewer\Ui\Component\Listing\Column;

use Fsm\LogViewer\Model\FileSizeFormatter;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Size extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FileSizeFormatter $fileSizeFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly FileSizeFormatter $fileSizeFormatter,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item[$fieldName])) {
                $item[$fieldName] = $this->fileSizeFormatter->format((int)$item[$fieldName]);
            }
        }
        unset($item);

        return $dataSource;
    }
}
