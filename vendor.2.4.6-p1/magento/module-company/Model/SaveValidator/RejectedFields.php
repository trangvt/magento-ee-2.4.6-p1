<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;

/**
 * Checks if company rejected fields are correct.
 */
class RejectedFields implements \Magento\Company\Model\SaveValidatorInterface
{
    /**
     * @var \Magento\Company\Api\Data\CompanyInterface
     */
    private $company;

    /**
     * @var \Magento\Framework\Exception\InputException
     */
    private $exception;

    /**
     * @var \Magento\Company\Api\Data\CompanyInterface
     */
    private $initialCompany;

    /**
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @param \Magento\Company\Api\Data\CompanyInterface $initialCompany
     * @param \Magento\Framework\Exception\InputException $exception
     */
    public function __construct(
        \Magento\Company\Api\Data\CompanyInterface $company,
        \Magento\Company\Api\Data\CompanyInterface $initialCompany,
        \Magento\Framework\Exception\InputException $exception
    ) {
        $this->company = $company;
        $this->initialCompany = $initialCompany;
        $this->exception = $exception;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (($this->isRejectedAtNotMatched() || $this->isRejectReasonNotMatched())
            && !$this->isCompanyStatusRejected()
        ) {
            $this->exception->addError(
                __(
                    'Invalid attribute value. Rejected date&time and Rejected Reason can be changed only'
                    . ' when a company status is changed to Rejected.'
                )
            );
        } elseif ($this->checkRejectedAtNotNullWhenStatusChangeToRejected()) {
            $this->exception->addError(
                __(
                    'Invalid attribute value. Rejected date&time and Rejected Reason can not be null'
                    . ' when a company status is changed to Rejected.'
                )
            );
        }
    }

    /**
     * Checking company rejected date time isset and matched
     *
     * @return bool
     */
    private function isRejectedAtNotMatched(): bool
    {
        $rejectedAt = $this->company->getRejectedAt();
        return (isset($rejectedAt) && $rejectedAt != $this->initialCompany->getRejectedAt());
    }

    /**
     * Checking company rejected reason isset and matched
     *
     * @return bool
     */
    private function isRejectReasonNotMatched(): bool
    {
        $rejectReason = $this->company->getRejectReason();
        return (isset($rejectReason) && $rejectReason != $this->initialCompany->getRejectReason());
    }

    /**
     * Checking company status is rejected or not
     *
     * @return bool
     */
    private function isCompanyStatusRejected(): bool
    {
        return ($this->company->getStatus() == CompanyInterface::STATUS_REJECTED
            && $this->initialCompany->getStatus() != CompanyInterface::STATUS_REJECTED);
    }

    /**
     * Check rejectedAt not null when status change to rejected
     *
     * @return bool
     */
    private function checkRejectedAtNotNullWhenStatusChangeToRejected(): bool
    {
        $rejectReason = $this->company->getRejectReason();
        return $this->company->getStatus() == CompanyInterface::STATUS_REJECTED && !isset($rejectReason);
    }
}
