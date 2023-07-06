<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\StatusServiceInterface;
use Magento\CompanyGraphQl\Model\Company\CreateCompanyAccount;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\ScopeInterface;

/**
 * Create company resolver.
 */
class CreateCompany implements ResolverInterface
{
    /**
     * @var CreateCompanyAccount
     */
    private $createCompanyAccount;

    /**
     * @var StatusServiceInterface
     */
    private $statusService;

    /**
     * @param CreateCompanyAccount $createCompanyAccount
     * @param StatusServiceInterface $statusService
     */
    public function __construct(
        CreateCompanyAccount $createCompanyAccount,
        StatusServiceInterface $statusService
    ) {
        $this->createCompanyAccount = $createCompanyAccount;
        $this->statusService = $statusService;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $store = $context->getExtensionAttributes()->getStore();
        if (!$this->statusService->isActive(ScopeInterface::SCOPE_STORE, $store->getCode())
            || !$this->statusService->isStorefrontRegistrationAllowed(ScopeInterface::SCOPE_STORE, $store->getCode())) {
            throw new GraphQlInputException(__('Company is not enabled or registration not allowed.'));
        }

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $company = $this->createCompanyAccount->execute(
            $args['input'],
            $context->getExtensionAttributes()->getStore()
        );
        if (!$company || !$company->getId()) {
            throw new GraphQlInputException(__('The company can not be created.'));
        }

        return [
            'company' => [
                'model' => $company,
                'isNewCompany' => true
            ]
        ];
    }
}
