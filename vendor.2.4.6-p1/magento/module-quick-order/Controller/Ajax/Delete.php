<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\QuickOrder\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\QuickOrder\Model\Config as ModuleConfig;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class for deleting products from quick order using AJAX
 */
class Delete implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param ModuleConfig $moduleConfig
     * @param JsonFactory $resultJsonFactory
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        JsonFactory $resultJsonFactory,
        Cart $cart
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cart = $cart;
        $this->request = $context->getRequest();
    }

    /**
     * Deletes product which SKU specified in request from cart
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->checkModuleIsEnabled();
        $requestData = $this->request->getPostValue();
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $generalErrorMessage = '';
        if (empty($requestData['sku'])) {
            $generalErrorMessage = __('There is no items to delete');
        } else {
            $this->cart->removeAffectedItem($requestData['sku']);
        }

        $data = [
            'generalErrorMessage' => (string) $generalErrorMessage,
        ];

        return $resultJson->setData($data);
    }

    /**
     * Checks that module is enabled
     *
     * @throws NotFoundException
     */
    private function checkModuleIsEnabled()
    {
        if (!$this->moduleConfig->isActive()) {
            throw new NotFoundException(__('Page not found.'));
        }
    }
}
