<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\CompanyGraphQl\Model\Company\Address\RegionLoader;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Prepare company data for save.
 */
class PrepareCompanyData
{
    /**
     * @var RegionLoader
     */
    private $loadRegion;

    /**
     * @param RegionLoader $loadRegion
     */
    public function __construct(
        RegionLoader $loadRegion
    ) {
        $this->loadRegion = $loadRegion;
    }

    /**
     * Convert raw data into a company compatible form.
     *
     * @param array $companyData
     * @return array
     * @throws GraphQlInputException
     */
    public function execute(array $companyData): array
    {
        if (!empty($companyData['company_admin'])) {
            $companyData = array_merge($companyData, $companyData['company_admin']);
        }

        if (!empty($companyData['legal_address'])) {
            $addressData = $companyData['legal_address'];
            unset($companyData['legal_address']);
            $companyData = array_merge($companyData, $addressData);

            if (!empty($companyData['region'])) {
                $regionData = $addressData['region'];
                unset($companyData['region']);
                $companyData = array_merge($companyData, $regionData);
                if (!isset($regionData['region_id'])) {
                    $region = $this->loadRegion->execute(
                        $addressData['country_id'] ?? null,
                        $regionData['region_id'] ?? null,
                        $regionData['region_code'] ?? null,
                        $regionData['region'] ?? null
                    );
                    if ($region && $region->getRegionId()) {
                        $companyData['region_id'] = $region->getRegionId();
                    } else {
                        throw new GraphQlInputException(
                            __(
                                'Invalid value of "%1" provided for the %2 field.',
                                isset($regionData['region_code']) ? $regionData['region_code'] : $regionData['region'],
                                isset($regionData['region_code']) ? 'region_code' : 'region'
                            )
                        );
                    }
                }

            }
        }

        return $companyData;
    }
}
