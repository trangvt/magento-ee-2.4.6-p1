<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableRequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\ConfigurableRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\ConfigurableOptionType;

class ConfigurableOptions implements ResolverInterface
{
    /**
     * @var Configuration
     */
    private $configurationHelper;

    /**
     * @var ConfigurableOptionType
     */
    private $configurableOptionDataProvider;

    /**
     * ConfigurableItemOptions constructor
     *
     * @param Configuration $configurationHelper
     * @param ConfigurableOptionType $configurableOptionDataProvider
     */
    public function __construct(
        Configuration $configurationHelper,
        ConfigurableOptionType $configurableOptionDataProvider
    ) {
        $this->configurationHelper = $configurationHelper;
        $this->configurableOptionDataProvider = $configurableOptionDataProvider;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !isset($value['product']['model'])) {
            throw new LocalizedException(__('"model" values should be specified'));
        }

        /** @var RequisitionListItem $item */
        $item = $value['model'];

        /** @var Product $product */
        $product = $value['product']['model'];

        $result = [];

        foreach ($this->configurableOptionDataProvider->getCustomOptions($item, $product) as $option) {
            $result[] = [
                'id' => $option['option_id'],
                'option_label' => $option['label'],
                'value_id' => $option['option_value'],
                'value_label' => $option['value'],
            ];
        }

        return $result;
    }
}
