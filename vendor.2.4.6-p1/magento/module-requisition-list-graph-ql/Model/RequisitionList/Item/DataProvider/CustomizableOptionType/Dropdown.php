<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionType;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\Select as SelectOptionType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue\PriceUnitLabel;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionInterface;

/**
 * Customizable Option Dropdown Value provider
 */
class Dropdown implements CustomizableOptionInterface
{
    /**
     * @var PriceUnitLabel
     */
    private $priceUnit;

    /**
     * Dropdown constructor
     *
     * @param PriceUnitLabel $priceUnit
     */
    public function __construct(
        PriceUnitLabel $priceUnit
    ) {
        $this->priceUnit = $priceUnit;
    }

    /**
     * @inheritdoc
     */
    public function getValues(
        Option $option,
        array $selectedOption
    ): array {
        try {
            /** @var SelectOptionType $optionTypeRenderer */
            $optionTypeRenderer = $option->groupFactory($option->getType())
                ->setOption($option);
        } catch (LocalizedException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        $selectedValue = $selectedOption['value'];
        $optionValue = $option->getValueById($selectedValue);
        $optionPriceType = (string)$optionValue->getPriceType();
        $priceValueUnits = $this->priceUnit->getData($optionPriceType);

        $selectedOptionValueData = [
            'id' => $selectedOption['option_id'],
            'label' => $optionTypeRenderer->getFormattedOptionValue($selectedValue),
            'value' => $selectedValue,
            'price' => [
                'type' => strtoupper($optionPriceType),
                'units' => $priceValueUnits,
                'value' => $optionValue->getPrice(),
            ]
        ];

        return [$selectedOptionValueData];
    }
}
