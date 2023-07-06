<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuoteGraphQl\Exception\GraphQlNegotiableQuoteInvalidStateException;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Quote model with related validation methods
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Quote
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface
     */
    private $customerRestriction;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @var NegotiableQuoteConverter
     */
    private $negotiableQuoteConverter;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $customerRestriction
     * @param IdEncoder $idEncoder
     * @param QuoteIdMask $quoteIdMaskResource
     * @param NegotiableQuoteConverter $negotiableQuoteConverter
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $customerRestriction,
        IdEncoder $idEncoder,
        QuoteIdMask $quoteIdMaskResource,
        NegotiableQuoteConverter $negotiableQuoteConverter
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerRestriction = $customerRestriction;
        $this->idEncoder = $idEncoder;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->negotiableQuoteConverter = $negotiableQuoteConverter;
    }

    /**
     * Get quote from id and verify it belongs to the current customer and website
     *
     * @param int $quoteId
     * @param WebsiteInterface $website
     * @param int[] $allowedCustomerIds
     * @return CartInterface
     * @throws GraphQlNoSuchEntityException
     */
    public function getOwnedQuote(
        int $quoteId,
        WebsiteInterface $website,
        array $allowedCustomerIds = []
    ): CartInterface {
        $errorMessage = 'Could not find a quote with the specified UID.';

        try {
            $quote = $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($errorMessage));
        }

        $validStoreIds = $website->getStoreIds();
        $this->customerRestriction->setQuote($quote);
        $customerId = (int)$quote->getCustomer()->getId();
        $canAccess = $this->customerRestriction->isOwner() || in_array($customerId, $allowedCustomerIds);
        if (!$canAccess || !in_array($quote->getStoreId(), $validStoreIds)) {
            throw new GraphQlNoSuchEntityException(__($errorMessage));
        }

        return $quote;
    }

    /**
     * Gets snapshot version of the quote.
     *
     * @param CartInterface $quote
     * @return CartInterface
     */
    public function getSnapshotQuote(CartInterface $quote): CartInterface
    {
        if ($quote->getExtensionAttributes()
            && $quote->getExtensionAttributes()->getNegotiableQuote()
            && $quote->getExtensionAttributes()->getNegotiableQuote()->getSnapshot()) {
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
            $snapshot = json_decode($negotiableQuote->getSnapshot(), true);
            if (is_array($snapshot)) {
                $quote = $this->negotiableQuoteConverter->arrayToQuote($snapshot);
            }
        }
        return $quote;
    }

    /**
     * Verify that the given quotes have negotiable quotes
     *
     * @param CartInterface[] $quotes
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function validateNegotiable(array $quotes): void
    {
        $nonNegotiableQuoteIds = [];
        foreach ($quotes as $quote) {
            if (!$quote->getExtensionAttributes()
                || !$quote->getExtensionAttributes()->getNegotiableQuote()
                || $quote->getExtensionAttributes()->getNegotiableQuote()->getQuoteId() === null) {
                $nonNegotiableQuoteIds[] = $quote->getId();
            }
        }
        if ($nonNegotiableQuoteIds) {
            $maskedIds = array_values($this->quoteIdMaskResource->getMaskedQuoteIds($nonNegotiableQuoteIds));
            throw new GraphQlInputException(
                __(
                    "The quotes with the following UIDs are not negotiable: "
                    . implode(", ", $maskedIds)
                )
            );
        }
    }

    /**
     * Verify that the quotes are in a status where the Submit action is allowed
     *
     * @param CartInterface[] $quotes
     * @throws GraphQlNegotiableQuoteInvalidStateException
     * @throws LocalizedException
     */
    public function validateCanSubmit(array $quotes): void
    {
        $cannotSubmitQuoteIds = [];
        foreach ($quotes as $quote) {
            $this->customerRestriction->setQuote($quote);
            if (!$this->customerRestriction->canSubmit()) {
                $cannotSubmitQuoteIds[] = $quote->getId();
            }
        }
        if ($cannotSubmitQuoteIds) {
            $maskedIds = array_values($this->quoteIdMaskResource->getMaskedQuoteIds($cannotSubmitQuoteIds));
            throw new GraphQlNegotiableQuoteInvalidStateException(
                __(
                    "The quotes with the following UIDs have a status that does not allow them to be edited "
                    . "or submitted: " . implode(", ", $maskedIds)
                )
            );
        }
    }

    /**
     * Verify that the quote is in a status where the Close action is allowed
     *
     * @param CartInterface $quote
     * @throws GraphQlNegotiableQuoteInvalidStateException
     * @throws LocalizedException
     */
    public function validateCanClose(CartInterface $quote): void
    {
        $this->customerRestriction->setQuote($quote);

        if (!$this->customerRestriction->canClose()) {
            throw new GraphQlNegotiableQuoteInvalidStateException(
                __("The quote has a status that does not allow it to be closed.")
            );
        }
    }

    /**
     * Verify that the quote is in a status where the Delete action is allowed
     *
     * @param CartInterface $quote
     * @throws GraphQlNegotiableQuoteInvalidStateException
     * @throws LocalizedException
     */
    public function validateCanDelete(CartInterface $quote): void
    {
        $this->customerRestriction->setQuote($quote);

        if (!$this->customerRestriction->canDelete()) {
            throw new GraphQlNegotiableQuoteInvalidStateException(
                __("The quote has a status that does not allow it to be deleted.")
            );
        }
    }

    /**
     * Validates that the given item ids exist on the quote
     *
     * @param CartInterface $quote
     * @param string[] $itemIds
     * @throws GraphQlNoSuchEntityException
     */
    public function validateHasItems(CartInterface $quote, array $itemIds): void
    {
        $quoteItemIds = [];
        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getItemsCollection() as $quoteItem) {
            $quoteItemIds[] = $quoteItem->getItemId();
        }
        $missingItemIds = array_diff($itemIds, $quoteItemIds);

        if ($missingItemIds) {
            throw new GraphQlNoSuchEntityException(
                __(
                    "The following item IDs were not found on the specified quote: "
                    . implode(", ", $this->idEncoder->encodeList($missingItemIds))
                )
            );
        }
    }

    /**
     * Verify that the quote is in a status where the Proceed to Checkout action is allowed
     *
     * @param CartInterface $quote
     * @throws GraphQlNegotiableQuoteInvalidStateException
     * @throws LocalizedException
     */
    public function validateCanProceedToCheckout(CartInterface $quote): void
    {
        $this->customerRestriction->setQuote($quote);
        if (!$this->customerRestriction->canProceedToCheckout()) {
            $maskedId = $this->quoteIdMaskResource->getMaskedQuoteId((int)$quote->getId());
            throw new GraphQlNegotiableQuoteInvalidStateException(
                __(
                    "The quote %quoteId is currently locked, and you cannot place an order from it at the moment.",
                    ['quoteId' => $maskedId]
                )
            );
        }
    }
}
