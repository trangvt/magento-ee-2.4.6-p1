<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionType;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\Text as TextOptionType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue\PriceUnitLabel;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionInterface;

/**
 * Customizable Option Text Value provider
 */
class Text implements CustomizableOptionInterface
{
    /**
     * @var PriceUnitLabel
     */
    private $priceUnitLabel;

    /**
     * Text constructor
     *
     * @param PriceUnitLabel $priceUnitLabel
     */
    public function __construct(
        PriceUnitLabel $priceUnitLabel
    ) {
        $this->priceUnitLabel = $priceUnitLabel;
    }

    /**
     * @inheritdoc
     */
    public function getValues(
        Option $option,
        array $selectedOption
    ): array {
        try {
            /** @var TextOptionType $optionTypeRenderer */
            $optionTypeRenderer = $option->groupFactory($option->getType());
        } catch (LocalizedException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        $optionTypeRenderer->setOption($option);
        $priceValueUnits = $this->priceUnitLabel->getData($option->getPriceType());

        $selectedOptionValueData = [
            'id' => $selectedOption['option_id'],
            'label' => '',
            'value' => $optionTypeRenderer->getFormattedOptionValue($selectedOption['value']),
            'price' => [
                'type' => strtoupper($option->getPriceType()),
                'units' => $priceValueUnits,
                'value' => $option->getPrice(),
            ],
        ];

        return [$selectedOptionValueData];
    }
}
