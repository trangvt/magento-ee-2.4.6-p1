<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Block\Cart\Item\Renderer\Actions;

use Magento\Checkout\Block\Cart\Item\Renderer\Actions\Generic;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;

/**
 * Cart renderer add to requisition block
 *
 * @api
 */
class AddToRequisition extends Generic
{
    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param Template\Context $context
     * @param HttpContext $httpContext
     * @param Json $jsonSerializer
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HttpContext $httpContext,
        Json $jsonSerializer,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->jsonSerializer = $jsonSerializer;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * Get product in cart item
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
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
     * Get product options
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOptions()
    {
        $options = [];
        $orderOptions = $this->getProduct()->getTypeInstance()->getOrderOptions($this->getProduct());
        if (isset($orderOptions['info_buyRequest'])) {
            $options = $orderOptions['info_buyRequest'] ?? null;

            /**
             * TODO: Remove this fix once the "Add to Cart" from requisition list page has been updated to include the
             *       missing product ID when converting the requisition list item into a cart item.
             */
            if (!isset($options['product'])) {
                $options['product'] = $this->getProduct()->getId();
            }

            $options['qty'] = $this->getQty();
            if (!empty($orderOptions['super_product_config']['product_id'])) {
                $superProductId = $orderOptions['super_product_config']['product_id'];
                $options['super_product_config'] = $orderOptions['super_product_config'];
            }
            $options['item'] = $superProductId ?? $this->getProduct()->getId();
            unset($options['uenc']);
            $options = http_build_query($options);
        }

        return $this->jsonSerializer->serialize($options);
    }

    /**
     * Get configurable product SKU
     *
     * @return string|null
     */
    public function getSku()
    {
        return $this->getProduct()->getData('sku');
    }

    /**
     * Get the cart/quote item id for the purposes of providing unique component id to the client.
     *
     * @return int
     */
    public function getComponentId()
    {
        return $this->getItem()->getId();
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $isCustomerLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $isCustomerLoggedIn ? parent::_toHtml() : '';
    }

    /**
     * Retrieve quote model object
     *
     * @return \Magento\Quote\Model\Quote
     */
    private function getQuoteData()
    {
        if (!$this->hasData('quote')) {
            $this->setData('quote', $this->checkoutSession->getQuote());
        }
        return $this->getData('quote');
    }

    /**
     * Get cart item qty
     *
     * @return float|int
     */
    private function getQty()
    {
        return $this->getItem()->getQty() * 1;
    }
}
