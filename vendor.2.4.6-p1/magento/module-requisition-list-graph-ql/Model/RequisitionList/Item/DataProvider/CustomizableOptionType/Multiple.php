<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionType;

use Magento\Catalog\Model\Product\Option;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue\PriceUnitLabel;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionInterface;

/**
 * Customizable Option Multiple Value provider
 */
class Multiple implements CustomizableOptionInterface
{
    /**
     * @var PriceUnitLabel
     */
    private $priceUnit;

    /**
     * Multiple constructor
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
        $selectedOptionValueData = [];
        $optionIds = explode(',', $selectedOption['value']);

        if (0 === count($optionIds)) {
            return $selectedOptionValueData;
        }

        foreach ($optionIds as $optionId) {
            $optionValue = $option->getValueById($optionId);
            $priceValueUnits = $this->priceUnit->getData($optionValue->getPriceType());

            $selectedOptionValueData[] = [
                'id' => $selectedOption['option_id'],
                'label' => $optionValue->getTitle(),
                'value' => $optionId,
                'price' => [
                    'type' => strtoupper($optionValue->getPriceType()),
                    'units' => $priceValueUnits,
                    'value' => $optionValue->getPrice(),
                ],
            ];
        }

        return $selectedOptionValueData;
    }
}
