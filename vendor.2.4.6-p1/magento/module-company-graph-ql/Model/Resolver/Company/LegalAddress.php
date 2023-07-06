<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company legal address data resolver, used for GraphQL request processing.
 */
class LegalAddress implements ResolverInterface
{
    /**
     * @var Region
     */
    private $regionResource;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param Region $regionResource
     * @param RegionFactory $regionFactory
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        Region $regionResource,
        RegionFactory $regionFactory,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->regionResource = $regionResource;
        $this->regionFactory = $regionFactory;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!isset($value['isNewCompany']) || $value['isNewCompany'] !== true) {
            $this->resolverAccess->isAllowed($this->allowedResources);
        }

        $company = $value['model'];
        $region = $this->regionFactory->create();
        $this->regionResource->load($region, $company->getRegionId());

        return [
            'street' => $company->getStreet(),
            'city' => $company->getCity(),
            'region' => [
                'region_code' => $region->getCode(),
                'region' => $region->getName(),
                'region_id' => $region->getId(),
            ],
            'country_code' => $company->getCountryId(),
            'postcode' => $company->getPostcode(),
            'telephone' => $company->getTelephone(),
        ];
    }
}
