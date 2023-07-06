<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Model\CompanyContext;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\UpdateCompanyAccount;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\ScopeInterface;

/**
 * Update company resolver.
 */
class UpdateCompany implements ResolverInterface
{
    /**
     * @var UpdateCompanyAccount
     */
    private $updateCompanyAccount;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var StatusServiceInterface
     */
    private $statusService;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param UpdateCompanyAccount $updateCompanyAccount
     * @param CompanyContext $companyContext
     * @param ResolverAccess $resolverAccess
     * @param StatusServiceInterface $statusService
     * @param array $allowedResources
     */
    public function __construct(
        UpdateCompanyAccount $updateCompanyAccount,
        CompanyContext $companyContext,
        ResolverAccess $resolverAccess,
        StatusServiceInterface $statusService,
        $allowedResources = []
    ) {
        $this->updateCompanyAccount = $updateCompanyAccount;
        $this->companyContext = $companyContext;
        $this->resolverAccess = $resolverAccess;
        $this->statusService = $statusService;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $store = $context->getExtensionAttributes()->getStore();
        if (!$this->statusService->isActive(ScopeInterface::SCOPE_STORE, $store->getCode())) {
            throw new GraphQlInputException(__('Company is not enabled.'));
        }

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $this->updateCompanyAccount->execute(
            $args['input'],
            $context->getUserId()
        );

        return [
            'company' => [
                'model' => $company
            ]
        ];
    }
}
