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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\CloseNegotiableQuotesForUser;

/**
 * Resolver for closing negotiable quotes
 */
class CloseNegotiableQuotes implements ResolverInterface
{
    /**
     * @var CloseNegotiableQuotesForUser
     */
    private $closeNegotiableQuotesForUser;

    /**
     * @param CloseNegotiableQuotesForUser $closeNegotiableQuotesForUser
     */
    public function __construct(CloseNegotiableQuotesForUser $closeNegotiableQuotesForUser)
    {
        $this->closeNegotiableQuotesForUser = $closeNegotiableQuotesForUser;
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
    ) {
        if (empty($args['input']['quote_uids'])) {
            throw new GraphQlInputException(__('Required parameter "quote_uids" is missing.'));
        }

        $maskedIds = $args['input']['quote_uids'];

        return $this->closeNegotiableQuotesForUser->execute(
            $maskedIds,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
    }
}
