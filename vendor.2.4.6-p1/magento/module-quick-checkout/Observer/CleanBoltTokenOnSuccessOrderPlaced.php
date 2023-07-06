<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer to clean bolt token after a successful order is placed
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CleanBoltTokenOnSuccessOrderPlaced implements ObserverInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Clean bolt token from session
     *
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        if ($this->customerSession->isLoggedIn()) {
            return;
        }

        $this->customerSession->unsBoltCustomerToken();
        $this->customerSession->unsCanUseBoltSso();
    }
}
