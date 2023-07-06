<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\SaveValidator;

use Laminas\Validator\EmailAddress;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Company\Model\SaveValidatorInterface;
use Magento\Framework\Exception\InputException;

/**
 * Checks if company email is valid.
 */
class CompanyEmail implements SaveValidatorInterface
{
    /**
     * @var CompanyInterface
     */
    private $company;

    /**
     * @var InputException
     */
    private $exception;

    /**
     * @var EmailAddress
     */
    private $emailValidator;

    /**
     * @var CollectionFactory
     */
    private $companyCollectionFactory;

    /**
     * @var CompanyInterface
     */
    private $initialCompany;

    /**
     * @param CompanyInterface $company
     * @param InputException $exception
     * @param EmailAddress $emailValidator
     * @param CollectionFactory $companyCollectionFactory
     * @param CompanyInterface $initialCompany
     */
    public function __construct(
        CompanyInterface $company,
        InputException $exception,
        EmailAddress $emailValidator,
        CollectionFactory $companyCollectionFactory,
        CompanyInterface $initialCompany
    ) {
        $this->company = $company;
        $this->exception = $exception;
        $this->emailValidator = $emailValidator;
        $this->companyCollectionFactory = $companyCollectionFactory;
        $this->initialCompany = $initialCompany;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!empty($this->company->getCompanyEmail())) {
            $isEmailAddress = $this->emailValidator->isValid($this->company->getCompanyEmail());

            if (!$isEmailAddress) {
                $this->exception->addError(
                    __(
                        'Invalid value of "%value" provided for the %fieldName field.',
                        ['fieldName' => 'company_email', 'value' => $this->company->getCompanyEmail()]
                    )
                );
            } elseif (!$this->company->getId()
                || strcasecmp($this->company->getCompanyEmail(), $this->initialCompany->getCompanyEmail()) !== 0
            ) {
                $collection = $this->companyCollectionFactory->create();
                $collection->addFieldToFilter(
                    CompanyInterface::COMPANY_EMAIL,
                    $this->company->getCompanyEmail()
                )->load();
                if ($collection->getSize()) {
                    $this->exception->addError(
                        __(
                            'Company with this email address already exists in the system.'
                            . ' Enter a different email address to continue.'
                        )
                    );
                }
            }
        }
    }
}
