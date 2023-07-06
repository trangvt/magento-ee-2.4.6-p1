<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Plugin\Checkout\Block;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session;
use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Payment\Model\Method\Free;

/**
 * Plugin for removing Store Credit if free payment method isn't available
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LayoutProcessorPlugin
{
    /**
     * @var CanUseForCompany
     */
    private $canUseForCompany;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Free
     */
    private $freeMethod;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param CanUseForCompany $canUseForCompany
     * @param Session $checkoutSession
     * @param Free $freeMethod
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        CanUseForCompany $canUseForCompany,
        Session $checkoutSession,
        Free $freeMethod,
        ArrayManager $arrayManager
    ) {
        $this->canUseForCompany = $canUseForCompany;
        $this->checkoutSession = $checkoutSession;
        $this->freeMethod = $freeMethod;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Remove store credit component if free payment not allowed
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(LayoutProcessor $subject, $jsLayout)
    {
        $quote = $this->checkoutSession->getQuote();
        $path = $this->arrayManager->findPath('storeCredit', $jsLayout);
        if ($path &&
            !$this->canUseForCompany->isApplicable($this->freeMethod, $quote)
        ) {
            $jsLayout = $this->arrayManager->remove($path, $jsLayout);
        }
        return $jsLayout;
    }
}
