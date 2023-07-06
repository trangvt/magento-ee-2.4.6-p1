<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Model\Company\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\User\Api\Data\UserInterface;

/**
 * Get list of Sales Representatives with their respective usernames as values
 */
class SalesRepresentativeUsernames implements OptionSourceInterface
{
    /**
     * @var Collection
     */
    private $adminUserCollection;

    /**
     * @param CollectionFactory $adminUserCollectionFactory
     */
    public function __construct(
        CollectionFactory $adminUserCollectionFactory
    ) {
        $this->adminUserCollection = $adminUserCollectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $adminUsers = $this->adminUserCollection->getItems();

        return array_map(function (UserInterface $adminUser) {
            return [
                'label' => $adminUser->getUserName(),
                'value' => $adminUser->getUserName(),
            ];
        }, $adminUsers);
    }
}
