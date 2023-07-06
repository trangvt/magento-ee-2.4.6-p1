<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Company\ViewModel;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Customer\Block\Widget\Name;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Company customer view model
 */
class Customer extends DataObject implements ArgumentInterface
{
    /**
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;

    /**
     * @var LayoutInterface
     */
    private $layoutInterface;

    /**
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param LayoutInterface $layoutInterface
     */
    public function __construct(
        CustomerInterfaceFactory $customerInterfaceFactory,
        LayoutInterface $layoutInterface
    ) {
        parent::__construct();
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->layoutInterface = $layoutInterface;
    }

    /**
     * Get customer name html
     *
     * @return string
     */
    public function getCustomerNameHtml(): string
    {
        $customerData = $this->customerInterfaceFactory->create();
        /** @var BlockInterface $blockCustomerName */
        $blockCustomerName = $this->layoutInterface->createBlock(Name::class)
            ->setObject($customerData);

        return $blockCustomerName->toHtml();
    }
}
