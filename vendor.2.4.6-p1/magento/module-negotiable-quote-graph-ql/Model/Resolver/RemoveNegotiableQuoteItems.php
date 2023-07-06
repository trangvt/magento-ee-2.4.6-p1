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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\IdEncoder;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\RemoveItemsFromNegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for removing items from a negotiable quote
 */
class RemoveNegotiableQuoteItems implements ResolverInterface
{
    /**
     * @var RemoveItemsFromNegotiableQuote
     */
    private $removeItemsFromNegotiableQuote;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param RemoveItemsFromNegotiableQuote $removeItemsFromNegotiableQuote
     * @param CartRepositoryInterface $quoteRepository
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        RemoveItemsFromNegotiableQuote $removeItemsFromNegotiableQuote,
        CartRepositoryInterface $quoteRepository,
        IdEncoder $idEncoder
    ) {
        $this->removeItemsFromNegotiableQuote = $removeItemsFromNegotiableQuote;
        $this->quoteRepository = $quoteRepository;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Remove Negotiable Quote Items
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

        if (empty($args['input']['quote_item_uids'])) {
            throw new GraphQlInputException(__('Required parameter "quote_item_uids" is missing.'));
        }

        $maskedId = $args['input']['quote_uid'];
        $quoteItemIds = $this->idEncoder->decodeList($args['input']['quote_item_uids']);
        $negotiableQuote = $this->removeItemsFromNegotiableQuote->execute(
            $maskedId,
            $quoteItemIds,
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
            'model' => $quote
        ];
        return $data;
    }
}
