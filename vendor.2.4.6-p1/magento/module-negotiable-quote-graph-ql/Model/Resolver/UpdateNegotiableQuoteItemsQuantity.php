<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\UpdateNegotiableQuoteItemsForUser;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for updating negotiable quote item quantities
 */
class UpdateNegotiableQuoteItemsQuantity implements ResolverInterface
{
    /**
     * @var UpdateNegotiableQuoteItemsForUser
     */
    private $updateNegotiableQuoteItemsForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * UpdateNegotiableQuoteItemsQuantity constructor
     *
     * @param UpdateNegotiableQuoteItemsForUser $updateNegotiableQuoteItemsForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        UpdateNegotiableQuoteItemsForUser $updateNegotiableQuoteItemsForUser,
        CartRepositoryInterface $cartRepository
    ) {
        $this->updateNegotiableQuoteItemsForUser = $updateNegotiableQuoteItemsForUser;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Update negotiable quote items quantity
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array[]
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (empty($args['input']['quote_uid'])) {
            throw new GraphQlInputException(__('Required parameter "quote_uid" is missing.'));
        }
        if (empty($args['input']['items'])) {
            throw new GraphQlInputException(__('Required parameter "items" is missing.'));
        }
        $this->validateQuantityValues($args['input']['items']);

        $items = $args['input']['items'];
        $maskedId = $args['input']['quote_uid'];

        $negotiableQuote = $this->updateNegotiableQuoteItemsForUser->execute(
            $maskedId,
            $items,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
        $quote = $this->cartRepository->get($negotiableQuote->getQuoteId());

        return [
            'quote' => [
                'uid' => $maskedId,
                'name' => $negotiableQuote->getQuoteName(),
                'created_at' => $quote->getCreatedAt(),
                'updated_at' => $quote->getUpdatedAt(),
                'status' => $negotiableQuote->getStatus(),
                'model' => $quote,
            ]
        ];
    }

    /**
     * Validates that quantities are > 0
     *
     * @param array $items
     * @throws GraphQlInputException
     */
    private function validateQuantityValues(array $items): void {
        $invalidQtyUids = [];
        foreach ($items as $item) {
            if ($item['quantity'] <= 0) {
                $invalidQtyUids[] = $item['quote_item_uid'];
            }
        }
        if (count($invalidQtyUids) > 0) {
            throw new GraphQlInputException(__('Quantity less than or equal to 0 is not allowed for item uids: ' . implode(',', $invalidQtyUids)));
        }
    }
}
