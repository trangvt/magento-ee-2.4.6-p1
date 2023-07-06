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
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Customer;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Quote;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;

/**
 * Resolver for retrieving a negotiable quote
 */
class GetNegotiableQuote implements ResolverInterface
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param RestrictionInterface $restriction
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        RestrictionInterface $restriction,
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->restriction = $restriction;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Get negotiable quote data by the quote id
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @throws GraphQlAuthorizationException
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
        if (empty($args['uid'])) {
            throw new GraphQlInputException(__("uid value must be specified."));
        }

        $this->customer->validateCanView((int)$context->getUserId());

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($args['uid']);
        $viewableCustomerIds = $this->customer->getViewableCustomerIds((int)$context->getUserId());
        $quote = $this->quote->getOwnedQuote(
            $quoteId,
            $context->getExtensionAttributes()->getStore()->getWebsite(),
            $viewableCustomerIds
        );
        $this->quote->validateNegotiable([$quote]);
        $negotiableQuote = $this->negotiableQuoteRepository->getById($quoteId);
        $this->restriction->setQuote($quote);
        $snapshotQuote = !$this->restriction->canSubmit() ? $this->quote->getSnapshotQuote($quote) : null;
        return [
            'uid' => $args['uid'],
            'name' => $negotiableQuote->getQuoteName(),
            'created_at' => $snapshotQuote ? $snapshotQuote->getCreatedAt() : $quote->getCreatedAt(),
            'updated_at' => $snapshotQuote ? $snapshotQuote->getUpdatedAt() : $quote->getUpdatedAt(),
            'status' => $negotiableQuote->getStatus(),
            'model' => $snapshotQuote ?: $quote
        ];
    }
}
