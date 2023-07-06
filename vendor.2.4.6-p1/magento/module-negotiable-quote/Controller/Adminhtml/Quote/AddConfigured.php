<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\NegotiableQuote\Block\Adminhtml\AdvancedCheckout\Sales\Order\Create\Sku\Add;

/**
 * AddConfigured controller action
 */
class AddConfigured extends Update implements HttpPostActionInterface
{
    /**
     * Update quote items
     *
     * @return Redirect
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');

        try {
            $updateData = $this->decode((string)$this->getRequest()->getParam('dataSend'));
            $this->quoteData = $updateData['quote'] ?? [];
            $this->quoteData['configuredItems'] = $this->getConfigurableItems();
            $this->quoteCurrency->updateQuoteCurrency($quoteId);
            $this->quoteUpdater->updateQuote($quoteId, $this->quoteData);
        } catch (Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError(__('Something went wrong'));
        }
        $data = $this->getQuoteData();
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setJsonData(json_encode($data, JSON_NUMERIC_CHECK));

        return $response;
    }

    /**
     * Retrieve configurable items data from request
     *
     * @return null|array
     */
    private function getConfigurableItems()
    {
        $configuredItems = $this->getRequest()->getParam(Add::LIST_TYPE, []);
        $configuredItemsParams = (array) $this->getRequest()->getParam('item');
        foreach ($configuredItems as $id => &$item) {
            $item['config'] = $configuredItemsParams[$id] ?? [];
        }
        return $configuredItems;
    }

    /**
     * Decode a value
     *
     * @param string $encodedValue
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function decode(string $encodedValue)
    {
        $decoded = json_decode($encodedValue, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Unable to unserialize value. Error: " . json_last_error_msg()
            );
        }

        return $decoded;
    }
}
