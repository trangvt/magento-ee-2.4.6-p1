<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Plugin;

use Magento\Company\Model\CompanyContext as CompanyContextModel;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company feature storefront availability.
 */
class CompanyContext
{
    /**
     * @var CompanyContextModel
     */
    private $companyContext;

    /**
     * CompanyFeatureActive constructor.
     *
     * @param CompanyContextModel $companyContext
     */
    public function __construct(
        CompanyContextModel $companyContext
    ) {
        $this->companyContext = $companyContext;
    }

    /**
     * Configure VersionManager before resolve
     *
     * @param ResolverInterface $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$this->companyContext->isModuleActive()) {
            throw new GraphQlInputException(__('Company features are not enabled for a storefront.'));
        }

        if (!$this->companyContext->isResourceAllowed($subject::COMPANY_RESOURCE)) {
            throw new GraphQlInputException(__('%1 access denied.', $subject::COMPANY_RESOURCE));
        }

        if (!$this->companyContext->isCurrentUserCompanyUser()) {
            throw new GraphQlInputException(__('Customer is not a company user.'));
        }

        return [$field, $context, $info, $value, $args];
    }
}
