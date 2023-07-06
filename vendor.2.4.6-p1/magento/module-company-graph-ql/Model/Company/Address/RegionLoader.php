<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Address;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;

/**
 * Load region by fields
 */
class RegionLoader
{
    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @param RegionCollectionFactory $regionCollectionFactory
     */
    public function __construct(
        RegionCollectionFactory $regionCollectionFactory
    ) {
        $this->regionCollectionFactory = $regionCollectionFactory;
    }

    /**
     * Execute loading region
     *
     * @param int $countryId
     * @param int|null $regionId
     * @param string|null $regionCode
     * @param string|null $regionName
     * @return Region|null
     */
    public function execute($countryId, ?int $regionId, ?string $regionCode, ?string $regionName): ?Region
    {
        $regionCollection = $this->regionCollectionFactory->create();

        if ($countryId) {
            $regionCollection->addCountryFilter($countryId);
        }

        $region = null;
        if ($regionId) {
            $region = $regionCollection->getItemById($regionId);
        } elseif ($regionCode || $regionName) {
            if ($regionCode) {
                $regionCollection->addRegionCodeFilter($regionCode);
            }
            if ($regionName) {
                $regionCollection->addRegionNameFilter($regionName);
            }
            if ($regionCollection->getSize()) {
                $region = $regionCollection->getFirstItem();
            }
        }

        return $region;
    }
}
