<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\RequestNegotiableQuoteForUser;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Resolver for creating new a negotiable quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class RequestNegotiableQuote implements ResolverInterface
{
    /**
     * @var RequestNegotiableQuoteForUser
     */
    private $requestNegotiableQuoteForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * RequestNegotiableQuote constructor
     *
     * @param RequestNegotiableQuoteForUser $requestNegotiableQuoteForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        RequestNegotiableQuoteForUser $requestNegotiableQuoteForUser,
        CartRepositoryInterface $cartRepository
    ) {
        $this->requestNegotiableQuoteForUser = $requestNegotiableQuoteForUser;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Request negotiable quote
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
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }

        if (empty($args['input']['quote_name'])) {
            throw new GraphQlInputException(__('Required parameter "quote_name" is missing.'));
        }

        if (empty($args['input']['comment']['comment'])) {
            throw new GraphQlInputException(__('Required parameter "comment" is missing.'));
        }

        $comment = $args['input']['comment']['comment'];
        $quoteName = $args['input']['quote_name'];
        $maskedId = $args['input']['cart_id'];

        $negotiableQuote = $this->requestNegotiableQuoteForUser->execute(
            $maskedId,
            $quoteName,
            $comment,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
        $quote = $this->cartRepository->get($negotiableQuote->getQuoteId());

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
