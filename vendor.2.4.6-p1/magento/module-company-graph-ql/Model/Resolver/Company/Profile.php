<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company profile data resolver, used for GraphQL request processing.
 */
class Profile implements ResolverInterface
{
    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param array $allowedResources
     */
    public function __construct(
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        array $allowedResources = []
    ) {
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
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
        $companyProfileData = [
            'id' => $this->idEncoder->encode((string)$company->getId()),
            'name' => $company->getCompanyName(),
            'email' => $company->getCompanyEmail(),
            'legal_name' => $company->getLegalName(),
            'vat_tax_id' => $company->getVatTaxId(),
            'reseller_id' => $company->getResellerId()
        ];
        return $companyProfileData[$info->fieldName];
    }
}
