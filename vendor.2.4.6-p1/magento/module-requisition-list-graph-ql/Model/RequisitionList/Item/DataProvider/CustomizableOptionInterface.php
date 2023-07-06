<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item\DataProvider;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Customizable Option Type Value provider
 */
interface CustomizableOptionInterface
{
    /**
     * Get Selected Option data from supported customizable options assigned to a Product
     *
     * @param Option $option
     * @param array $selectedOption
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function getValues(
        Option $option,
        array $selectedOption
    ): array;
}
