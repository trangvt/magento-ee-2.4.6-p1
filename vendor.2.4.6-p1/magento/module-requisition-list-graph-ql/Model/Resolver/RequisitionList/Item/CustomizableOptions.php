<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionInterface;

class CustomizableOptions implements ResolverInterface
{
    /**
     * @var CustomizableOptionInterface
     */
    private $customizableOption;

    /**
     * CustomizableOptions constructor
     *
     * @param CustomizableOptionInterface $customizableOption
     */
    public function __construct(
        CustomizableOptionInterface $customizableOption
    ) {
        $this->customizableOption = $customizableOption;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !isset($value['product']['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var RequisitionListItem $requisitionListItem */
        $requisitionListItem = $value['model'];

        /** @var Product $product */
        $product = $value['product']['model'];

        if (!isset($requisitionListItem->getOptions()['option_ids'])) {
            return [];
        }

        $options = $requisitionListItem->getOptions()['option_ids'];
        $customizableOptionsData = [];
        $customizableOptionIds = explode(',', $options);

        foreach ($customizableOptionIds as $customizableOptionId) {
            if (!$option = $product->getOptionById((int)$customizableOptionId)) {
                $customizableOptionsData[] = [];
                continue;
            }

            $selectedOption = [
                'value' => $requisitionListItem->getOptions()['option_' . $option->getId()],
                'option_id' => $option->getId()
            ];

            $selectedOptionValueData = $this->customizableOption->getValues($option, $selectedOption);

            $customizableOptionsData[] = [
                'id' => $option->getId(),
                'label' => $option->getTitle(),
                'values' => $selectedOptionValueData,
                'sort_order' => $option->getSortOrder(),
                'is_required' => $option->getIsRequire(),
            ];
        }

        return $customizableOptionsData;
    }
}
