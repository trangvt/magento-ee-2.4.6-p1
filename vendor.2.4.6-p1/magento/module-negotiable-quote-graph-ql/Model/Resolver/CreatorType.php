<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for the negotiable quote creator type
 */
class CreatorType implements ResolverInterface
{
    const BUYER = 'buyer';
    const SELLER = 'seller';
    const COMMENT_CREATOR_TYPE_ENUM = 'NegotiableQuoteCommentCreatorType';

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
     * Get the negotiable quote creator type
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): string {
        if (!isset($value['creator_type'])) {
            throw new LocalizedException(__('"creator_type" value must be specified.'));
        }
        $type = $value['creator_type'];
        if ($type == UserContextInterface::USER_TYPE_ADMIN) {
            $value = self::SELLER;
        } else {
            $value = self::BUYER;
        }

        return $this->enumLookup->getEnumValueFromField(self::COMMENT_CREATOR_TYPE_ENUM, $value);
    }
}
