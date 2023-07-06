<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Laminas\Validator\EmailAddress;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company admin email validator resolver, used for GraphQL request processing.
 */
class CompanyUserEmailChecker implements ResolverInterface
{

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var EmailAddress
     */
    private $emailValidator;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyContext $companyContext
     * @param EmailAddress $emailValidator
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        CompanyContext $companyContext,
        EmailAddress $emailValidator
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->companyContext = $companyContext;
        $this->emailValidator = $emailValidator;
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
        if (!isset($args['email'])) {
            throw new GraphQlInputException(__('Required parameter "email" is missing'));
        }

        if (!$this->companyContext->isModuleActive()) {
            throw new GraphQlInputException(__('Company feature is not available.'));
        }

        return [
            'is_email_available' => $this->isCustomerEmailValid($args['email'])
        ];
    }

    /**
     * Is customer email valid
     *
     * @param string $email
     * @return bool
     * @throws GraphQlInputException|LocalizedException
     */
    private function isCustomerEmailValid(string $email): bool
    {
        $isEmailAddress = $this->emailValidator->isValid($email);

        if (!$isEmailAddress) {
            throw new GraphQlInputException(__(
                'Invalid value of "%value" provided for the %fieldName field.',
                ['fieldName' => 'email', 'value' => $email]
            ));
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(CustomerInterface::EMAIL, $email)
            ->create();

        return !$this->customerRepository->getList($searchCriteria)->getTotalCount();
    }
}
