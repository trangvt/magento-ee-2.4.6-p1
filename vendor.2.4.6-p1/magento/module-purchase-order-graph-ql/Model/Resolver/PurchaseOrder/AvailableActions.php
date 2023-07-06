<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver\PurchaseOrder;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategy;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Purchase Order available_actions field resolver
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AvailableActions implements ResolverInterface
{
    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var ValidatorLocator
     */
    private ValidatorLocator $validatorLocator;

    /**
     * @var DeferredPaymentStrategy
     */
    private DeferredPaymentStrategy $deferredPaymentStrategy;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var array
     */
    private array $actions;

    /**
     * @param Authorization $authorization
     * @param ValidatorLocator $validatorLocator
     * @param DeferredPaymentStrategy $deferredPaymentStrategy
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerSession $customerSession
     * @param array $actions
     */
    public function __construct(
        Authorization $authorization,
        ValidatorLocator $validatorLocator,
        DeferredPaymentStrategy $deferredPaymentStrategy,
        CartRepositoryInterface $quoteRepository,
        CustomerSession $customerSession,
        array $actions = []
    ) {
        $this->authorization = $authorization;
        $this->validatorLocator = $validatorLocator;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->actions = $actions;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        return $this->getData($value['model']);
    }

    /**
     * Retrieve available actions for purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return array
     * @throws LocalizedException
     */
    private function getData(PurchaseOrderInterface $purchaseOrder): array
    {
        $availableActions = [];
        foreach ($this->actions as $key => $action) {
            if ($this->isAllowedAction($purchaseOrder, $key)) {
                if ($key === 'placeorder'
                    && $this->isPaymentRequired($purchaseOrder)
                    && $this->hasQuoteIssues($purchaseOrder)
                ) {
                    continue;
                }
                $availableActions[] = $action;
            }
        }
        return $availableActions;
    }

    /**
     * Check if the purchase order requires payment from current customer
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     * @throws LocalizedException
     */
    public function isPaymentRequired(PurchaseOrderInterface $purchaseOrder): bool
    {
        $poStatus = $purchaseOrder->getStatus();

        if ($poStatus === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT &&
            $this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder) &&
            $this->isCreator($purchaseOrder)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if purchase order has error
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     */
    public function hasQuoteIssues(PurchaseOrderInterface $purchaseOrder): bool
    {
        $quote = $this->getQuote($purchaseOrder);
        return !$quote || !$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount();
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
     * Check if current customer is purchase order creator
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     */
    private function isCreator(PurchaseOrderInterface $purchaseOrder): bool
    {
        return ((int) $purchaseOrder->getCreatorId()) === ((int) $this->customerSession->getCustomerId());
    }

    /**
     * Check is action allowed on current purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param string $action
     * @return bool
     */
    private function isAllowedAction(PurchaseOrderInterface $purchaseOrder, string $action) : bool
    {
        return $this->validatorLocator->getValidator($action)->validate($purchaseOrder)
            && $this->authorization->isAllowed($action, $purchaseOrder);
    }
}
