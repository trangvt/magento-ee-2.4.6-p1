<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ResourceModel\TaxClass\Collection as TaxClassCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var SharedCatalogFactory $sharedCatalogFactory */
$sharedCatalogFactory = $objectManager->get(SharedCatalogFactory::class);
/** @var GroupRepository $groupRepository */
$groupRepository = $objectManager->create(GroupRepository::class);
/** @var GroupInterfaceFactory $customerGroup */
$groupFactory = $objectManager->create(GroupInterfaceFactory::class);
/** @var TaxClassCollection $taxClassCollection */
$taxClassCollection = $objectManager->create(TaxClassCollection::class);

// Input data for shared catalogs
$sharedCatalogsData = [
    [
        'name' => 'Company 0 shared catalog',
        'description' => 'Shared catalog designed for company type 0',
    ],
    [
        'name' => 'Company 1 shared catalog',
        'description' => 'Shared catalog designed for company type 1',
    ],
    [
        'name' => 'Company 2 shared catalog',
        'description' => 'Shared catalog designed for company type 2',
    ]
];

// Get tax class
/** @var ClassModel $taxClass */
$taxClass = $taxClassCollection->getLastItem();
$taxClassId = $taxClass->getId();

// Loop and save entities using the input data
foreach ($sharedCatalogsData as $sharedCatalogsEntry) {
    //Create customer group
    $customerGroup = $groupFactory->create(
        [
            'data' => [
                'code' => $sharedCatalogsEntry['name'],
                'tax_class_id' => $taxClassId
            ]
        ]
    );
    $customerGroup = $groupRepository->save($customerGroup);

    //Create shared catalog
    $sharedCatalog = $sharedCatalogFactory->create();
    $sharedCatalog->setName($sharedCatalogsEntry['name']);
    $sharedCatalog->setDescription($sharedCatalogsEntry['description']);
    $sharedCatalog->setType(0);
    $sharedCatalog->setCreatedBy(null);
    $sharedCatalog->setTaxClassId($taxClassId);
    $sharedCatalog->setCustomerGroupId($customerGroup->getId());
    $sharedCatalog->setStoreId(0);
    $sharedCatalog->save();
}
