<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Laminas\Validator\EmailAddress;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company email validator resolver, used for GraphQL request processing.
 */
class CompanyEmailChecker implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

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
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyContext $companyContext
     * @param EmailAddress $emailValidator
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CompanyRepositoryInterface $companyRepository,
        CompanyContext $companyContext,
        EmailAddress $emailValidator
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->companyRepository = $companyRepository;
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
            'is_email_available' => $this->isCompanyEmailValid($args['email'])
        ];
    }

    /**
     * Is company email valid
     *
     * @param string $email
     * @return bool
     * @throws GraphQlInputException|LocalizedException
     */
    private function isCompanyEmailValid(string $email): bool
    {
        $isEmailAddress = $this->emailValidator->isValid($email);

        if (!$isEmailAddress) {
            throw new GraphQlInputException(__(
                'Invalid value of "%value" provided for the %fieldName field.',
                ['fieldName' => 'email', 'value' => $email]
            ));
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(CompanyInterface::COMPANY_EMAIL, $email)
            ->create();

        return !$this->companyRepository->getList($searchCriteria)->getTotalCount();
    }
}
