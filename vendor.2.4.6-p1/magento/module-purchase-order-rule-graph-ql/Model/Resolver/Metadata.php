<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\GetRoleData;
use Magento\PurchaseOrderRuleGraphQl\Model\Roles;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolver for the purchase order rule metadata
 */
class Metadata implements ResolverInterface
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var Roles
     */
    private Roles $roles;

    /**
     * @var GetRoleData
     */
    private GetRoleData $getRoleData;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param ResolverAccess $resolverAccess
     * @param CompanyManagementInterface $companyManagement
     * @param Roles $roles
     * @param GetRoleData $getRoleData
     * @param array $allowedResources
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        ResolverAccess $resolverAccess,
        CompanyManagementInterface $companyManagement,
        Roles $roles,
        GetRoleData $getRoleData,
        array $allowedResources = []
    ) {
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->resolverAccess = $resolverAccess;
        $this->companyManagement = $companyManagement;
        $this->roles = $roles;
        $this->getRoleData = $getRoleData;
        $this->allowedResources = $allowedResources;
    }

    /**
     * Resolve PurchaseOrderApprovalRuleMetadata type
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        /** @var \Magento\GraphQl\Model\Query\ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $this->companyManagement->getByCustomerId($context->getUserId());

        return [
            'available_applies_to' => $this->getRolesData(
                $this->roles->getRoles((int) $company->getId())
            ),
            'available_condition_currencies' => $this->getCurrencies(),
            'available_requires_approval_from' => $this->getRolesData(
                $this->roles->getApproverRoles((int) $company->getId())
            ),
        ];
    }

    /**
     * Retrieve currencies array
     *
     * @return array
     */
    private function getCurrencies(): array
    {
        $currencies = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $code = $website->getBaseCurrencyCode();
            $currencies[] = [
                'code' => $code,
                'symbol' => $this->priceCurrency->getCurrencySymbol(null, $code)
            ];
        }
        return $currencies;
    }

    /**
     * Retrieve roles data formatted for GraphQL response
     *
     * @param array $roles
     * @return array
     */
    private function getRolesData(array $roles): array
    {
        return array_map(
            function (RoleInterface $role) {
                return $this->getRoleData->execute($role);
            },
            $roles
        );
    }
}
