<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order;

/**
 * Retrieve formatted purchase order data for GraphQL response
 */
class GetPurchaseOrderData
{
    /**
     * @var Order
     */
    private Order $orderFormatter;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var ExtractCustomerData
     */
    private ExtractCustomerData $extractCustomerData;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param Order $orderFormatter
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param ExtractCustomerData $extractCustomerData
     * @param Uid $uid
     */
    public function __construct(
        Order $orderFormatter,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        ExtractCustomerData $extractCustomerData,
        Uid $uid
    ) {
        $this->orderFormatter = $orderFormatter;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->quoteRepository = $quoteRepository;
        $this->extractCustomerData = $extractCustomerData;
        $this->uid = $uid;
    }

    /**
     * Retrieve formatted purchase order data for GraphQL response
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return array
     */
    public function execute(PurchaseOrderInterface $purchaseOrder): array
    {
        $order = $this->getOrder($purchaseOrder);
        $customer = $this->getCustomer($purchaseOrder);
        $customerData = $customer
            ? ['model' => $customer, ...$this->extractCustomerData->execute($customer)]
            : null;
        return [
            'uid' => $this->uid->encode((string)$purchaseOrder->getEntityId()),
            'number' => $purchaseOrder->getIncrementId(),
            'order' =>  $order ? $this->orderFormatter->format($order) : null,
            'quote' => ['model' => $this->getQuote($purchaseOrder)],
            'created_at' => $purchaseOrder->getCreatedAt(),
            'updated_at' => $purchaseOrder->getUpdatedAt(),
            'created_by' => $customerData,
            'status' => strtoupper($purchaseOrder->getStatus()),
            'model' => $purchaseOrder
        ];
    }

    /**
     * Get the Quote for the Purchase Order currently being viewed.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return CartInterface|null
     */
    public function getQuote(PurchaseOrderInterface $purchaseOrder): ?CartInterface
    {
        $snapshotQuote = $purchaseOrder->getSnapshotQuote();

        if ($snapshotQuote->getItemsCount()) {
            return $snapshotQuote;
        }

        try {
            return $this->quoteRepository->get($purchaseOrder->getQuoteId(), ['*']);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Retrieve customer
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return CustomerInterface|null
     */
    private function getCustomer(PurchaseOrderInterface $purchaseOrder): ?CustomerInterface
    {
        if (!$purchaseOrder->getCreatorId()) {
            return null;
        }
        try {
            $customer = $this->customerRepository->getById($purchaseOrder->getCreatorId());
        } catch (LocalizedException $exception) {
            return null;
        }

        return $customer;
    }

    /**
     * Retrieve order for purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return OrderInterface|null
     */
    private function getOrder(PurchaseOrderInterface $purchaseOrder): ?OrderInterface
    {
        if (!$purchaseOrder->getOrderId()) {
            return null;
        }
        try {
            return $this->orderRepository->get($purchaseOrder->getOrderId());
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
