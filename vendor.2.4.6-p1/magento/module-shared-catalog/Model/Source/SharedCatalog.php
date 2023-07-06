<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Source;

use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;

/**
 * Class SharedCatalog for Model Source
 */
class SharedCatalog implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var SharedCatalogRepositoryInterface
     */
    protected $sharedCatalogRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get shared catalogs list
     *
     * @return \Magento\SharedCatalog\Api\Data\SharedCatalogInterface[]
     */
    protected function getCatalogList()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $item */
        foreach ($this->getCatalogList() as $item) {
            $options[] = [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }

        return $options;
    }
}
