<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for the type of change which occurred in a negotiable quote history entry
 */
class HistoryEntryChangeType implements ResolverInterface
{
    /**
     * Constant Buyer label
     */
    const BUYER = 'buyer';

    /**
     * Constant Seller label
     */
    const SELLER = 'seller';

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @param EnumLookup $enumLookup
     */
    public function __construct(EnumLookup $enumLookup)
    {
        $this->enumLookup = $enumLookup;
    }

    /**
     * Get the history entry change type
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return string
     * @throws LocalizedException
     * @throws RuntimeException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): string {
        if (!isset($value['change_type'])) {
            throw new LocalizedException(__('"change_type" value must be specified.'));
        }
        $changeType = $value['change_type'];

        return $this->enumLookup->getEnumValueFromField('NegotiableQuoteHistoryEntryChangeType', $changeType);
    }
}
