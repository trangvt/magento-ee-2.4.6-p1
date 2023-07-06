<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Block\Checkout\Cart\Addto;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;

/**
 * Add items in the cart to requisition
 *
 * @api
 */
class Requisition extends \Magento\Framework\View\Element\Template
{
    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $jsonEncoder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Json $jsonEncoder
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Json $jsonEncoder,
        HttpContext $httpContext,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->checkoutSession = $checkoutSession;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve quote model object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuoteData()
    {
        if (!$this->hasData('quote')) {
            $this->setData('quote', $this->checkoutSession->getQuote());
        }
        return $this->getData('quote');
    }

    /**
     * Get Cart Product Data
     *
     * @return string
     */
    public function getProductData()
    {
        $items = $this->getQuoteData()->getAllVisibleItems();
        $result = [];
        foreach ($items as $item) {
            $product['sku'] = $item->getProduct()->getData('sku');
            $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
            $customOptions = $options['info_buyRequest'] ?? null;

            /**
             * TODO: Remove this fix once the "Add to Cart" from requisition list page has been updated to include the
             *       missing product ID when converting the requisition list item into a cart item.
             */
            if (!isset($customOptions['product'])) {
                $customOptions['product'] = $item->getProduct()->getId();
            }
            if (!empty($options['super_product_config']['product_id'])) {
                $customOptions['super_product_config'] = $options['super_product_config'];
                $customOptions['item'] = $options['super_product_config']['product_id'];
            }
            if ($customOptions) {
                $customOptions['qty'] = $item->getQty();
                $product['options'] = http_build_query($customOptions);
            }
            $result[] = $product;
        }

        return $this->jsonEncoder->serialize($result);
    }

    /**
     * Check if cart has error
     *
     * @return bool
     */
    public function hasCartError()
    {
        return $this->getQuoteData()->getHasError();
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $cartItems = $this->getQuoteData()->getItemsCount();
        $isCustomerLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);

        return $isCustomerLoggedIn && $cartItems && !$this->hasCartError() ? parent::_toHtml() : '';
    }
}
