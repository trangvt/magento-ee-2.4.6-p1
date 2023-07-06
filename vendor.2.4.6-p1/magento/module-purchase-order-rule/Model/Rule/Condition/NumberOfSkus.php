<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule\Condition;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\AbstractCondition;

/**
 * Number of SKUs condition for Purchase Order approval rules
 */
class NumberOfSkus extends AbstractCondition
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->setType(NumberOfSkus::class);
        $this->cartRepository = $cartRepository;
    }

    /**
     * Retrieve address from whichever model type was passed and validate against number of SKUs
     *
     * @param AbstractModel|PurchaseOrderInterface|CartInterface $model
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validate(AbstractModel $model)
    {
        if ($model instanceof PurchaseOrderInterface) {
            /* @var Quote $quote */
            $quote = $this->cartRepository->get($model->getQuoteId());
            /* @var QuoteAddress $model */
            $model = $this->getQuoteAddress($quote);
        } elseif ($model instanceof Quote) {
            $model = $model->getQuoteAddress($model);
        }
        $uniqueSkus = [];
        foreach ($model->getAllItems() as $item) {
            /* @var QuoteItem $item */
            if ($item->getSku() && !in_array($item->getSku(), $uniqueSkus)) {
                array_push($uniqueSkus, $item->getSku());
            }
        }

        return $this->validateAttribute(count($uniqueSkus));
    }

    /**
     * Get correct address if quote is virtual
     *
     * @param Quote $quote
     * @return QuoteAddress
     */
    private function getQuoteAddress(Quote $quote)
    {
        return $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
    }
}
