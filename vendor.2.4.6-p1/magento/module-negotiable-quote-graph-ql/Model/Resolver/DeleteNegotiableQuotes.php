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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\DeleteNegotiableQuotesForUser;

/**
 * Resolver for deleting negotiable quotes
 */
class DeleteNegotiableQuotes implements ResolverInterface
{
    /**
     * @var DeleteNegotiableQuotesForUser
     */
    private $deleteNegotiableQuotesForUser;

    /**
     * @param DeleteNegotiableQuotesForUser $deleteNegotiableQuotesForUser
     */
    public function __construct(DeleteNegotiableQuotesForUser $deleteNegotiableQuotesForUser)
    {
        $this->deleteNegotiableQuotesForUser = $deleteNegotiableQuotesForUser;
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

        return $this->deleteNegotiableQuotesForUser->execute(
            $maskedIds,
            (int)$context->getUserId(),
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
    }
}
