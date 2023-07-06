<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Users as CompanyUsers;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer associated company users
 */
class Users implements ResolverInterface
{
    /**
     * @var CompanyUsers
     */
    private $companyUsers;

    /**
     * @var ExtractCustomerData
     */
    private $customerData;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param CompanyUsers $companyUsers
     * @param ExtractCustomerData $customerData
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        CompanyUsers $companyUsers,
        ExtractCustomerData $customerData,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->companyUsers = $companyUsers;
        $this->customerData = $customerData;
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
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        if (isset($value['isNewCompany']) && $value['isNewCompany'] === true) {
            return null;
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $value['model'];
        $searchResults = $this->companyUsers->getCompanyUsers($company, $args);
        $companyUsers = [];

        foreach ($searchResults->getItems() as $companyUser) {
            $companyUsers[] = $this->customerData->execute($companyUser);
        }

        $pageSize = $searchResults->getSearchCriteria()->getPageSize();

        return [
            'items' => $companyUsers,
            'total_count' => $searchResults->getTotalCount(),
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $searchResults->getSearchCriteria()->getCurrentPage(),
                'total_pages' => $pageSize ? ((int)ceil($searchResults->getTotalCount() / $pageSize)) : 0
            ]
        ];
    }
}
