<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Plugin\Company\Model;

use Magento\Backend\Model\UrlInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company\Save;
use Magento\Company\Model\Email\Sender;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class EmailNotification
 *
 * Email notification plugin notify customer withe emails
 * after create company account through API
 */
class EmailNotification
{
    /**
     * @var Sender
     */
    private $companyEmailSender;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * EmailNotification constructor
     *
     * @param Sender $companyEmailSender
     * @param UrlInterface $urlBuilder
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Sender $companyEmailSender,
        UrlInterface $urlBuilder,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->companyEmailSender = $companyEmailSender;
        $this->urlBuilder = $urlBuilder;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Notifying customer after creating company account through API
     *
     * @param Save $subject
     * @param CompanyInterface $company
     * @return CompanyInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        Save $subject,
        CompanyInterface $company
    ): CompanyInterface {
        if ($company && $company->isObjectNew()) {
            $customerData = $this->customerRepository
                ->getById(
                    $company->getSuperUserId()
                );
            $companyUrl = $this->urlBuilder
                ->getUrl(
                    'company/index/edit',
                    ['id' => $company->getEntityId()]
                );
            $this->companyEmailSender->sendAdminNotificationEmail(
                $customerData,
                $company->getCompanyName(),
                $companyUrl
            );
        }
        return $company;
    }
}
