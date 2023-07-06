<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Export view model for requisition list
 */
class Export implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get url for exporting requisition list
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->urlBuilder->getUrl(
            '*/*/export',
            [
                'requisition_id' => (int) $this->request->getParam('requisition_id')
            ]
        );
    }
}
