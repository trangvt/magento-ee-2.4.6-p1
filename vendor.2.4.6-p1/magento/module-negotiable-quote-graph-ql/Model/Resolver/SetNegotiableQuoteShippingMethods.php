<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\SetNegotiableQuoteShippingMethods as SetShippingMethodsModel;

/**
 * Resolver for setting the shipping method on a negotiable quote
 */
class SetNegotiableQuoteShippingMethods implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var SetShippingMethodsModel
     */
    private $setNegotiableQuoteShippingMethods;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param SetShippingMethodsModel $setNegotiableQuoteShippingMethods
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        SetShippingMethodsModel $setNegotiableQuoteShippingMethods
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->setNegotiableQuoteShippingMethods = $setNegotiableQuoteShippingMethods;
    }

    /**
     * @inheritDoc
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
        $shippingMethods = $args['input']['shipping_methods'] ?? [];

        $negotiableQuote = $this->setNegotiableQuoteShippingMethods->execute(
            $context,
            $maskedId,
            $shippingMethods
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
