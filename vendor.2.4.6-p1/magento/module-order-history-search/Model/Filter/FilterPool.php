<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class FilterPool.
 *
 * Filter pool model for order history search filter pool.
 */
class FilterPool
{
    /**
     * @var string[]
     */
    private $filtersClassMap;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface|null
     */
    private $objectManager = null;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string[] $filtersClassMap
     */
    public function __construct(ObjectManagerInterface $objectManager, array $filtersClassMap = [])
    {
        $this->objectManager = $objectManager;
        $this->filtersClassMap = $filtersClassMap;
    }

    /**
     * Get class instance with specified parameters
     *
     * @param string $filterName
     *
     * @return FilterInterface
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $filterName): FilterInterface
    {
        if (key_exists($filterName, $this->filtersClassMap)) {
            $object = $this->objectManager->get($this->filtersClassMap[$filterName]);

            if (!$object instanceof FilterInterface) {
                throw new \InvalidArgumentException('Filter class must implement FilterInterface.');
            }

            return $object;
        }

        throw new \InvalidArgumentException('Filter with specified name does not exist.');
    }
}
