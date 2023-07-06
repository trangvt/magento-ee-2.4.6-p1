<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\AddItemsToCart;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Add purchase order items to customer shopping cart
 */
class AddItem extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var AddItemsToCart
     */
    private $addItemsToCart;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param Session $checkoutSession
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param AddItemsToCart $addItemsToCart
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        Session $checkoutSession,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        AddItemsToCart $addItemsToCart
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->checkoutSession = $checkoutSession;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->addItemsToCart = $addItemsToCart;
    }

    /**
     * Execute action add purchase order items to shopping cart, replace the existing shopping cart items if requested
     *
     * @return Redirect
     */
    public function execute() : Redirect
    {
        $requestId = (int)$this->getRequest()->getParam('request_id');
        $replaceCart = $this->getRequest()->getParam('replace_cart') == 1 ? true : false;

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($requestId);
            $errors = $this->addItemsToCart->execute($this->checkoutSession->getQuote(), $purchaseOrder, $replaceCart);
            if (count($errors) > 0) {
                $this->messageManager->addErrorMessage(
                    __(
                        "Some Item(s) are not available and are not added into the shopping cart."
                    )
                );
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $resultRedirect->setPath("checkout/cart");
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     * Verify that the user can view the purchase order from the request.
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function isAllowed() : bool
    {
        $purchaseOrderId = $this->_request->getParam('request_id');
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
        return parent::isAllowed()
            && $this->purchaseOrderActionAuth->isAllowed('View', $purchaseOrder);
    }
}
