<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\ProcessorInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new purchase order
 */
class PurchaseOrderFromQuote implements RevertibleDataFixtureInterface
{
    private const PARAM_CART_ID = 'cart_id';

    private const DEFAULT_DATA = [
        self::PARAM_CART_ID => null,
        'payment' => [
            'po_number' => null,
            'method' => 'checkmo',
            'additional_data' => null
        ]
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $purchaseOrderProcessor;

    /**
     * @var PaymentInterfaceFactory
     */
    private PaymentInterfaceFactory $paymentFactory;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @param ServiceFactory $serviceFactory
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $purchaseOrderProcessor
     * @param PaymentInterfaceFactory $paymentFactory
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        DataMerger $dataMerger,
        ProcessorInterface $purchaseOrderProcessor,
        PaymentInterfaceFactory $paymentFactory,
        CartRepositoryInterface $cartRepository
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataMerger = $dataMerger;
        $this->purchaseOrderProcessor = $purchaseOrderProcessor;
        $this->paymentFactory = $paymentFactory;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (!isset($data[self::PARAM_CART_ID])) {
            throw new InvalidArgumentException(__('"%field" is required', ['field' => self::PARAM_CART_ID]));
        }
        $data = $this->dataMerger->merge(self::DEFAULT_DATA, $data);
        $quote = $this->cartRepository->get($data[self::PARAM_CART_ID]);
        $payment = $this->paymentFactory->create(['data' => $data['payment']]);
        return $this->purchaseOrderProcessor->createPurchaseOrder($quote, $payment);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->serviceFactory->create(PurchaseOrderRepositoryInterface::class, 'delete')->execute(
            [
                'purchaseOrder' => $data
            ]
        );
    }
}
