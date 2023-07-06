<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Cart;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Clear Customer Cart
 */
class ClearCartItems
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * ClearCartItems constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * Clears Cart items
     *
     * @param string $maskedCartId
     * @return CartInterface
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function clearCartItems(string $maskedCartId): CartInterface
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        try {
            $cart = $this->cartRepository->get($cartId);
            $cart->removeAllItems();
            $this->cartRepository->save($cart);
        } catch (Exception $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()), $exception);
        }

        return $cart;
    }
}
