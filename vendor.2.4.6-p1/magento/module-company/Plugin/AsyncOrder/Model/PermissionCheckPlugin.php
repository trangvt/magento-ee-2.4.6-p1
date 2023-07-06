<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Company\Plugin\AsyncOrder\Model;

use Magento\AsyncOrder\Model\AsyncPaymentInformationCustomerPublisher;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Before plugin for permission check
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class PermissionCheckPlugin
{

    /**
     * @var Session
     */
    private $userContext;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @param Session $userContext
     * @param CompanyManagementInterface $companyManagement
     */
    public function __construct(
        Session $userContext,
        CompanyManagementInterface $companyManagement
    ) {
        $this->userContext = $userContext;
        $this->companyManagement = $companyManagement;
    }

    /**
     * Before placing order permission check
     *
     * @param AsyncPaymentInformationCustomerPublisher $subject
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws AuthorizationException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        AsyncPaymentInformationCustomerPublisher $subject,
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $customerId = (int)$this->userContext->getCustomer()->getId() ?: 0;
        if ($customerId) {
            $company = $this->companyManagement->getByCustomerId($customerId);
            if ($company !== null && $company->getExtensionAttributes()->getIsPurchaseOrderEnabled()) {
                throw new AuthorizationException(__(
                    'You are not authorized to access this resource.'
                ));
            }
        }
        return null;
    }
}
