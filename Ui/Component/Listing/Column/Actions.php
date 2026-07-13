<?php
declare(strict_types=1);

/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

namespace Fsm\LogViewer\Ui\Component\Listing\Column;

use Fsm\LogViewer\Api\LogFileServiceInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param LogFileServiceInterface $logFileService
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly LogFileServiceInterface $logFileService,
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

        $canDownload = $this->logFileService->canDownload();
        $name = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['name'])) {
                continue;
            }
            $file = $item['name'];
            $item[$name]['view'] = [
                'href' => $this->urlBuilder->getUrl('fsm_log_viewer/log/view', ['file' => $file]),
                'label' => __('View'),
            ];
            if ($canDownload) {
                $item[$name]['download'] = [
                    'href' => $this->urlBuilder->getUrl('fsm_log_viewer/log/download', ['file' => $file]),
                    'label' => __('Download'),
                ];
            }
        }
        unset($item);

        return $dataSource;
    }
}
