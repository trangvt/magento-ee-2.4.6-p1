<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Company feature access.
 */
class ResolverAccess
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::index';

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @param CompanyContext $companyContext
     */
    public function __construct(
        CompanyContext $companyContext
    ) {
        $this->companyContext = $companyContext;
    }

    /**
     * Validate company requests
     *
     * @param array $allowedResources
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAllowed(array $allowedResources): void
    {
        if (!$this->companyContext->isModuleActive()) {
            throw new GraphQlInputException(__('Company feature is not available.'));
        }

        if (!$this->companyContext->isCurrentUserCompanyUser()) {
            throw new GraphQlInputException(__('Customer is not a company user.'));
        }

        if ($allowedResources) {
            $allowedResources[] = self::COMPANY_RESOURCE;
        }

        foreach ($allowedResources as $allowedResource) {
            if (!$this->companyContext->isResourceAllowed($allowedResource)) {
                throw new GraphQlInputException(__('You do not have authorization to perform this action.'));
            }
        }
    }
}
