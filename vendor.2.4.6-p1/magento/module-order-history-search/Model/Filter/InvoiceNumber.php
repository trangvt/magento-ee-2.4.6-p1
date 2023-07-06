<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class InvoiceNumber.
 *
 * Model for 'Invoice Number' filter for order search filter.
 */
class InvoiceNumber implements FilterInterface
{
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * InvoiceNumber constructor.
     *
     * @param InvoiceRepository $invoiceRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        InvoiceRepository $invoiceRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * @inheritdoc
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        /** @var SearchCriteriaBuilder $invoiceCriteria */
        $invoiceCriteria = $this->searchCriteriaBuilderFactory->create();
        $invoiceCriteria->addFilter(InvoiceInterface::INCREMENT_ID, '%' . $value . '%', 'like');

        $invoices = $this->invoiceRepository->getList($invoiceCriteria->create());

        $orderIds = [];
        foreach ($invoices as $invoice) {
            $orderIds[] = $invoice->getOrderId();
        }

        $ordersCollection->addFieldToFilter(OrderInterface::ENTITY_ID, ['in' => $orderIds]);

        return $ordersCollection;
    }
}
