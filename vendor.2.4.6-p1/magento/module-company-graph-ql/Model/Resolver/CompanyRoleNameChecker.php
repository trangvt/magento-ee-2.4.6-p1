<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Laminas\Validator\LessThan;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company role name validator resolver, used for GraphQL request processing.
 */
class CompanyRoleNameChecker implements ResolverInterface
{
    /**
     * Maximum length for a role name
     */
    private const ROLE_NAME_MAX_LENGTH = 40;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CompanyUser $companyUser
     * @param RoleRepositoryInterface $roleRepository
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CompanyUser $companyUser,
        RoleRepositoryInterface $roleRepository,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->companyUser = $companyUser;
        $this->roleRepository = $roleRepository;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        if (!isset($args['name'])) {
            throw new GraphQlInputException(__('Required parameter "name" is missing'));
        }

        return [
            'is_role_name_available' => $this->isCompanyRoleNameValid($args['name'])
        ];
    }

    /**
     * Is company role name valid.
     *
     * @param string $roleName
     * @return bool
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isCompanyRoleNameValid(string $roleName): bool
    {
        $lengthValidator = new LessThan(['max' => self::ROLE_NAME_MAX_LENGTH]);
        if (!$lengthValidator->isValid(strlen(trim($roleName)))) {
            throw new GraphQlInputException(__(
                'Company role name must not be more than %1 characters.',
                self::ROLE_NAME_MAX_LENGTH
            ));
        }

        $companyId = $this->companyUser->getCurrentCompanyId();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(RoleInterface::ROLE_NAME, $roleName)
            ->addFilter(RoleInterface::COMPANY_ID, $companyId)
            ->create();

        return !$this->roleRepository->getList($searchCriteria)->getTotalCount();
    }
}
