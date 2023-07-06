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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\GetAllNegotiableQuoteByFilter;

/**
 * Resolver for retrieving all negotiable quotes for user
 */
class GetNegotiableQuotes implements ResolverInterface
{
    /**
     * @var GetAllNegotiableQuoteByFilter
     */
    private $getNegotiableQuoteByFilter;

    /**
     * @param GetAllNegotiableQuoteByFilter $getNegotiableQuoteByFilter
     */
    public function __construct(GetAllNegotiableQuoteByFilter $getNegotiableQuoteByFilter)
    {
        $this->getNegotiableQuoteByFilter = $getNegotiableQuoteByFilter;
    }

    /**
     * Get all negotiable quote data for the current user
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
        $currentPage = isset($args['currentPage']) ? (int)$args['currentPage'] : 1;
        $pageSize = isset($args['pageSize']) ? (int)$args['pageSize'] : 20;

        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($pageSize < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        $filterArgs = $args['filter'] ?? [];

        $sortArgs = $args['sort'] ?? [];

        return $this->getNegotiableQuoteByFilter->execute(
            $filterArgs,
            $currentPage,
            $pageSize,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite(),
            $sortArgs
        );
    }
}
