<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Setup\Patch\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Fix share catalog table after changing store id reference
 */
class RemoveInvalidStoreGroupIdInShareCatalog implements DataPatchInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        foreach ($this->sharedCatalogRepository->getList($searchCriteria)->getItems() as $sharedCatalog) {
            if ($sharedCatalog->getStoreId() !== null && $sharedCatalog->getStoreId() !== 0) {
                try {
                    $groupId = (int) $this->storeManager->getGroup($sharedCatalog->getStoreId())->getId();
                } catch (NoSuchEntityException $storeGroupNotFoundException) {
                    try {
                        $store = $this->storeManager->getStore($sharedCatalog->getStoreId());
                        $groupId = (int) $this->storeManager->getGroup($store->getStoreGroupId())->getId();
                    } catch (NoSuchEntityException $notFoundException) {
                        // if group id cannot be resolved, assign to all groups
                        $groupId = 0;
                    }
                }
                if ($sharedCatalog->getStoreId() !== $groupId) {
                    $sharedCatalog->setStoreId($groupId);
                    $this->sharedCatalogRepository->save($sharedCatalog);
                }
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
