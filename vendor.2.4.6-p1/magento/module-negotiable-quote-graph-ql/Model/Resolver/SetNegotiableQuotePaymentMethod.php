<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\SetNegotiableQuotePaymentMethod
    as SetNegotiableQuotePaymentMethodModel;
use Magento\Quote\Api\CartRepositoryInterface;

class SetNegotiableQuotePaymentMethod implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var SetNegotiableQuotePaymentMethodModel
     */
    private $setNegotiableQuotePaymentMethod;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param SetNegotiableQuotePaymentMethodModel $setNegotiableQuotePaymentMethod
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        SetNegotiableQuotePaymentMethodModel $setNegotiableQuotePaymentMethod
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->setNegotiableQuotePaymentMethod = $setNegotiableQuotePaymentMethod;
    }

    /**
     * Set negotiable quote payment method resolver
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
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
        $maskedQuoteId = $args['input']['quote_uid'];

        if (empty($args['input']['payment_method']['code'])) {
            throw new GraphQlInputException(__('Required parameter "code" for "payment_method" is missing.'));
        }
        $paymentData = $args['input']['payment_method'];

        $negotiableQuote = $this->setNegotiableQuotePaymentMethod->execute(
            $maskedQuoteId,
            $paymentData,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
        $quote = $this->quoteRepository->get($negotiableQuote->getQuoteId());

        return [
            'quote' => [
                'uid' => $args['input']['quote_uid'],
                'name' => $negotiableQuote->getQuoteName(),
                'created_at' => $quote->getCreatedAt(),
                'updated_at' => $quote->getUpdatedAt(),
                'status' => $negotiableQuote->getStatus(),
                'model' => $quote
            ]
        ];
    }
}
