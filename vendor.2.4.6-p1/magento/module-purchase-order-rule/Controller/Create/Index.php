<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Create;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Controller class for purchase order rule form.
 */
class Index extends AbstractController implements HttpGetActionInterface
{
    /**
     * Required resource for action authorization.
     */
    const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * Index constructor.
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderConfig $purchaseOrderConfig
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderConfig $purchaseOrderConfig
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
    }

    /**
     * Purchase order rule form.
     *
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Create New Approval Rule'));
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');

        if ($navigationBlock) {
            $navigationBlock->setActive('purchaseorderrule/');
        }

        return $resultPage;
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     * Verify that the user has the required permission to perform the action.
     *
     * @return bool
     */
    protected function isAllowed()
    {
        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && $this->companyContext->isResourceAllowed(self::COMPANY_RESOURCE);
    }
}
