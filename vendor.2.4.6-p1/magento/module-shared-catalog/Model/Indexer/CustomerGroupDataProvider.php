<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\Indexer;

use ArrayIterator;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Indexer\DimensionProviderInterface;
use Traversable;

/**
 * The data provider for customer group
 */
class CustomerGroupDataProvider implements DimensionProviderInterface
{
    /**
     * Name for customer group dimension for multidimensional indexer
     * 'cg' - stands for 'customer_group'
     */
    const DIMENSION_NAME = 'cg';

    /**
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * @var ArrayIterator
     */
    private $customerGroupsDataIterator;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var GroupInterface
     */
    private $customerGroup;

    /**
     * @param GroupManagementInterface $groupManagement
     * @param DimensionFactory $dimensionFactory
     * @param GroupInterface $customerGroup
     */
    public function __construct(
        GroupManagementInterface $groupManagement,
        DimensionFactory $dimensionFactory,
        GroupInterface $customerGroup
    ) {
        $this->dimensionFactory = $dimensionFactory;
        $this->groupManagement = $groupManagement;
        $this->customerGroup = $customerGroup;
    }

    /**
     * Gets iterator.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->getCustomerGroups() as $customerGroup) {
            yield $this->dimensionFactory->create(self::DIMENSION_NAME, (string) $customerGroup);
        }
    }

    /**
     * Gets customer groups.
     *
     * @return ArrayIterator
     */
    private function getCustomerGroups(): ArrayIterator
    {
        if ($this->customerGroupsDataIterator === null) {
            $this->customerGroupsDataIterator = new ArrayIterator([$this->customerGroup->getId()]);
        }

        return $this->customerGroupsDataIterator;
    }
}
