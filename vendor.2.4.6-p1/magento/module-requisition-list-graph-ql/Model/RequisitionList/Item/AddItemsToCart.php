<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForCustomer;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForGuest;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector;

/**
 * Adds Requisition List Items to cart
 */
class AddItemsToCart
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var RequisitionListManagementInterface
     */
    private $listManagement;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var ItemSelector
     */
    private $itemSelector;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CreateEmptyCartForCustomer
     */
    private $createEmptyCartForCustomer;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var CreateEmptyCartForGuest
     */
    private $createEmptyCartForGuest;

    /**
     * AddItemsToCart constructor
     *
     * @param RequisitionListManagementInterface $listManagement
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param ItemSelector $itemSelector
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CreateEmptyCartForCustomer $createEmptyCartForCustomer
     * @param CreateEmptyCartForGuest $createEmptyCartForGuest
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        RequisitionListManagementInterface $listManagement,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        ItemSelector $itemSelector,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreateEmptyCartForCustomer $createEmptyCartForCustomer,
        CreateEmptyCartForGuest $createEmptyCartForGuest,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->listManagement = $listManagement;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->itemSelector = $itemSelector;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->createEmptyCartForCustomer = $createEmptyCartForCustomer;
        $this->createEmptyCartForGuest = $createEmptyCartForGuest;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * Adds items to Cart
     *
     * @param int $customerId
     * @param array $itemIds
     * @param int $requisitionListId
     * @param int $websiteId
     * @return CartInterface
     * @throws GraphQlInputException
     */
    public function execute(
        int $customerId,
        array $itemIds,
        int $requisitionListId,
        int $websiteId
    ): CartInterface {
        $cart = null;
        try {
            $this->errors = [];
            $maskedCartId = (0 === $customerId || null === $customerId)
                ? $this->createEmptyCartForGuest->execute($customerId)
                : $this->createEmptyCartForCustomer->execute($customerId);

            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            if (!empty($itemIds)) {
                $items = $this->itemSelector->selectItemsFromRequisitionList(
                    $requisitionListId,
                    $itemIds,
                    $websiteId
                );
            } else {
                $items = $this->itemSelector->selectAllItemsFromRequisitionList(
                    $requisitionListId,
                    $websiteId
                );
            }

            $this->listManagement->placeItemsInCart($cartId, $items);
            $this->errors = $this->listManagement->errors;

            $cart = $this->cartRepository->get($cartId);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(
                __('Unable to add Requisition list items to cart')
            );
        }

        return $cart;
    }

    /**
     * Returns items add to cart errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        $addToCartErrors = [];
        $errors = $this->errors;
        foreach ($errors as $errorData) {
            foreach ($errorData as $type => $error) {
                $addToCartErrors[] = ['type' => strtoupper($type), 'message' => $error->getText()];
            }
        }
        return $addToCartErrors;
    }
}
