<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionType;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\CustomizableOptionInterface;

class Composite implements CustomizableOptionInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $customizableOptionTypes;

    public function __construct(
        ObjectManagerInterface $objectManager,
        array $customizableOptionTypes = []
    ) {
        $this->objectManager = $objectManager;
        $this->customizableOptionTypes = $customizableOptionTypes;
    }

    /**
     * @param Option $option
     * @param array $selectedOption
     * @return array
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function getValues(Option $option, array $selectedOption): array
    {
        $optionType = $option->getType();
        if (!array_key_exists($optionType, $this->customizableOptionTypes)) {
            throw new GraphQlInputException(__('Option type "%1" is not supported', $optionType));
        }
        $customizableOptionValueClassName = $this->customizableOptionTypes[$optionType];

        $customizableOptionValue = $this->objectManager->get($customizableOptionValueClassName);
        if (!$customizableOptionValue instanceof CustomizableOptionInterface) {
            throw new LocalizedException(
                __('%1 doesn\'t implement CustomizableOptionInterface', $customizableOptionValueClassName)
            );
        }
        return $customizableOptionValue->getValues($option, $selectedOption);
    }
}
