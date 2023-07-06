<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\PurchaseOrder;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrderRule\Model\Validator;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Controller class for Validating Purchase Order
 */
class Validate extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var Validator
     */
    private $purchaseOrderValidator;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param Validator $purchaseOrderValidator
     * @param CommentManagement $commentManagement
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderConfig $purchaseOrderConfig,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        Validator $purchaseOrderValidator,
        CommentManagement $commentManagement
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->purchaseOrderValidator = $purchaseOrderValidator;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->commentManagement = $commentManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        $purchaseOrderId = $this->getRequest()->getParam('request_id');
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
            $this->purchaseOrderValidator->validate($purchaseOrder);

            // If there is a comment present in the request include it as part of the operation
            if ($this->getRequest()->getParam('comment')) {
                $this->commentManagement->addComment(
                    $purchaseOrder->getEntityId(),
                    $this->companyContext->getCustomerId(),
                    $this->getRequest()->getParam('comment')
                );
            }

            $this->messageManager->addSuccessMessage(__('Purchase order has been successfully validated.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to validate purchase order.'));
        }
        return $resultRedirect;
    }

    /**
     * Get current purchase order.
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     */
    private function getPurchaseOrder() : PurchaseOrderInterface
    {
        $requestId = (int)$this->getRequest()->getParam('request_id');
        return $this->purchaseOrderRepository->getById($requestId);
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
        try {
            return $this->purchaseOrderActionAuth->isAllowed('validate', $this->getPurchaseOrder());
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
