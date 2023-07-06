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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\SetNegotiableQuoteBillingAddress
    as SetNegotiableQuoteBillingAddressModel;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for setting the billing address on a negotiable quote
 */
class SetNegotiableQuoteBillingAddress implements ResolverInterface
{
    /**
     * @var SetNegotiableQuoteBillingAddressModel
     */
    private $setNegotiableQuoteBillingAddress;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param SetNegotiableQuoteBillingAddressModel $setNegotiableQuoteBillingAddress
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        SetNegotiableQuoteBillingAddressModel $setNegotiableQuoteBillingAddress
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->setNegotiableQuoteBillingAddress = $setNegotiableQuoteBillingAddress;
    }

    /**
     * Set negotiable quote billing address resolver
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

        if (empty($args['input']['billing_address'])) {
            throw new GraphQlInputException(__('Required parameter "billing_address" is missing'));
        }
        $billingAddress = $args['input']['billing_address'];

        $negotiableQuote = $this->setNegotiableQuoteBillingAddress->execute(
            $context,
            $maskedId,
            $billingAddress
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
