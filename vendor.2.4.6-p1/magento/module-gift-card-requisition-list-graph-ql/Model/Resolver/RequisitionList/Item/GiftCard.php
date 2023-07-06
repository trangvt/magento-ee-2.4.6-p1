<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardRequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\GiftCardRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\GiftCardOptionType;

class GiftCard implements ResolverInterface
{
    /**
     * @var GiftCardOptionType
     */
    private $giftCardType;

    public function __construct(
        GiftCardOptionType $giftCardType
    ) {
        $this->giftCardType = $giftCardType;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !isset($value['product']['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var RequisitionListItem $requisitionListItem */
        $requisitionListItem = $value['model'];

        return $this->giftCardType->getProductData($requisitionListItem, $context);
    }
}
