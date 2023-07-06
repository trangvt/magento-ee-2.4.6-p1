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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\SendNegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for sending a negotiable quote for seller review with an optional comment
 */
class SendNegotiableQuoteForReview implements ResolverInterface
{
    /**
     * @var SendNegotiableQuote
     */
    private $sendNegotiableQuote;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param SendNegotiableQuote $sendNegotiableQuote
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(SendNegotiableQuote $sendNegotiableQuote, CartRepositoryInterface $quoteRepository)
    {
        $this->sendNegotiableQuote = $sendNegotiableQuote;
        $this->quoteRepository = $quoteRepository;
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
        $comment = $args['input']['comment']['comment'] ?? '';

        $negotiableQuote = $this->sendNegotiableQuote->execute(
            $maskedId,
            $comment,
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
