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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\SetNegotiableQuoteShippingAddressForUser;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for setting the shipping address on a negotiable quote
 */
class SetNegotiableQuoteShippingAddress implements ResolverInterface
{
    /**
     * @var SetNegotiableQuoteShippingAddressForUser
     */
    private $setNegotiableQuoteShippingAddressForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param SetNegotiableQuoteShippingAddressForUser $setNegotiableQuoteShippingAddressForUser
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        SetNegotiableQuoteShippingAddressForUser $setNegotiableQuoteShippingAddressForUser
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->setNegotiableQuoteShippingAddressForUser = $setNegotiableQuoteShippingAddressForUser;
    }

    /**
     * Set negotiable quote shipping address resolver
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
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

        $maskedId = $args['input']['quote_uid'];
        $shippingAddresses = $args['input']['shipping_addresses'] ?? [];

        // Need this for backwards compatability of deprecated field.
        if (isset($args['input']['customer_address_id'])) {
            if (isset($args['input']['shipping_addresses'])) {
                throw new GraphQlInputException(
                    __('You cannot set multiple shipping addresses in the same call.' . ' We recommend using ' .
                        'the `shipping_address` type. The `customer_address_id` field is deprecated.')
                );
            }
            $shippingAddresses[0]['customer_address_uid'] = $args['input']['customer_address_id'];
        }

        $negotiableQuote = $this->setNegotiableQuoteShippingAddressForUser->execute(
            $maskedId,
            $shippingAddresses,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
        $quote = $this->quoteRepository->get($negotiableQuote->getQuoteId());

        $data['quote'] = [
            'uid' => $maskedId,
            'name' => $negotiableQuote->getQuoteName(),
            'created_at' => $quote->getCreatedAt(),
            'updated_at' => $quote->getUpdatedAt(),
            'status' => $negotiableQuote->getStatus(),
            'model' => $quote,
        ];
        return $data;
    }
}
