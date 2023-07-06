<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\QuickOrder\Controller\Ajax;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\QuickOrder\Controller\AbstractAction;
use Magento\QuickOrder\Model\Config as ModuleConfig;

/**
 * Search products by SKUs using ajax request.
 */
class Search extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param Context $context
     * @param ModuleConfig $moduleConfig
     * @param ResultJsonFactory $resultJsonFactory
     * @param Cart $cart
     * @param JsonSerializer $jsonSerializer
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        ResultJsonFactory $resultJsonFactory,
        Cart $cart,
        JsonSerializer $jsonSerializer,
        PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct($context, $moduleConfig);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cart = $cart;
        $this->jsonSerializer = $jsonSerializer;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get info about products, which SKU specified in request
     *
     * @return ResultJson
     */
    public function execute()
    {
        $requestData = $this->getRequest()->getPostValue();
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $generalErrorMessage = '';
        $items = $this->jsonSerializer->unserialize($requestData['items']);
        $items = $this->removeEmptySkuItems($items);
        if (empty($items)) {
            $generalErrorMessage = $this->getErrorMessage();
        } else {
            $this->cart->setContext(Cart::CONTEXT_FRONTEND);
            $this->cart->removeAllAffectedItems();
            $this->cart->prepareAddProductsBySku($items);
            $items = $this->cart->getAffectedItems();
            foreach ($items as $key => $item) {
                $items[$key]['price'] = $this->priceCurrency->convertAndFormat($item['price'], false);
            }
        }

        $data = [
            'generalErrorMessage' => (string) $generalErrorMessage,
            'items' => $items,
        ];
        $failedItems = $this->cart->getFailedItems();
        foreach ($failedItems as $failed) {
            $this->cart->removeAffectedItem($failed[ProductInterface::SKU]);
        }

        return $resultJson->setData($data);
    }

    /**
     * Retrieve error message by type if item list is empty.
     *
     * @return Phrase
     */
    private function getErrorMessage(): Phrase
    {
        $generalErrorMessage = __('Cannot update item list.');

        if (!($errorType = $this->getRequest()->getPostValue('errorType', false))) {
            return $generalErrorMessage;
        }

        switch ($errorType) {
            case 'multiple':
                $generalErrorMessage = __('Entered list is empty.');
                break;

            case 'item':
                $generalErrorMessage = __('You entered item(s) with an empty SKU.');
                break;

            case 'file':
                $generalErrorMessage = __(
                    'The uploaded CSV file does not contain a column labelled SKU. ' .
                    'Make sure the first column is labelled SKU and that each line in the file contains a SKU value. ' .
                    'Then upload the file again.'
                );
                break;
        }

        return $generalErrorMessage;
    }

    /**
     * Remove items if SKU is empty
     *
     * @param array $items
     * @return array
     */
    protected function removeEmptySkuItems(array $items)
    {
        foreach ($items as $k => $item) {
            if (!isset($item['sku']) || trim($item['sku']) === '') {
                unset($items[$k]);
            }
        }

        return $items;
    }
}
