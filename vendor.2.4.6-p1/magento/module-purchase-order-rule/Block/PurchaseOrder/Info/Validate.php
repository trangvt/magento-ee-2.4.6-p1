<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\PurchaseOrder\Info;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the validate button on the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Validate extends AbstractPurchaseOrder
{
    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var ValidatorLocator
     */
    private $validatorLocator;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param Authorization $authorization
     * @param ValidatorLocator $validatorLocator
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        Authorization $authorization,
        ValidatorLocator $validatorLocator,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->authorization = $authorization;
        $this->validatorLocator = $validatorLocator;
    }
    /**
     * Check is action allowed on current purchase order.
     *
     * @param string $action
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isAllowedAction(string $action) : bool
    {
        return $this->validatorLocator->getValidator($action)->validate($this->getPurchaseOrder())
            && $this->authorization->isAllowed($action, $this->getPurchaseOrder());
    }

    /**
     * Gets the url to cancel the currently viewed purchase order.
     *
     * @return string
     * @since 100.2.0
     */
    public function getValidateUrl() : string
    {
        return $this->getUrl(
            'purchaseorderrule/purchaseorder/validate',
            ['request_id' => $this->_request->getParam('request_id')]
        );
    }

    /**
     * Checks if the currently viewed purchase order can be deleted.
     *
     * @return bool
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function canValidate() : bool
    {
        return $this->isAllowedAction('validate');
    }
}
