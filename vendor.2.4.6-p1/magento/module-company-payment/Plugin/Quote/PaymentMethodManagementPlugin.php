<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Plugin\Quote;

/**
 * Class PaymentMethodManagementPlugin.
 */
class PaymentMethodManagementPlugin
{
    /**
     * Company check identifier.
     *
     * @var string
     */
    private $companyCheck = 'company';

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Payment\Model\Checks\SpecificationFactory
     */
    private $methodSpecificationFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->methodSpecificationFactory = $methodSpecificationFactory;
    }

    /**
     * Around getList.
     *
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @return \Magento\Quote\Api\Data\PaymentMethodInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetList(
        \Magento\Quote\Api\PaymentMethodManagementInterface $subject,
        $proceed,
        $cartId
    ) {
        $result = $proceed($cartId);
        $quote = $this->quoteRepository->get($cartId);
        $specification = $this->methodSpecificationFactory->create($this->companyCheck);

        foreach ($result as $key => $paymentMethod) {
            if (!$specification->isApplicable($paymentMethod, $quote)) {
                unset($result[$key]);
            }
        }

        return $result;
    }
}
