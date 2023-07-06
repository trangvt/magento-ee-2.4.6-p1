<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\QuickOrder\Plugin\AdvancedCheckout;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\QuickOrder\Model\Config;

/**
 * Plugin class for updating affected items before products preparation in AdvancedCheckoutCart class.
 * @see \Magento\Checkout\Model\Cart\CartInterface
 */
class PrepareAddProductsBySkuPlugin
{
    /**
     * @var Config
     */
    private $quickOrderConfig;

    /**
     * @param Config $quickOrderConfig
     */
    public function __construct(
        Config $quickOrderConfig
    ) {
        $this->quickOrderConfig = $quickOrderConfig;
    }

    /**
     * Change item data to use it in the QuickOrder
     *
     * @param Cart $subject
     * @param array $items
     * @return void
     */
    public function beforePrepareAddProductsBySku(
        Cart $subject,
        array $items
    ): void {
        if ($this->quickOrderConfig->isActive()) {
            $affectedItems = $subject->getAffectedItems();
            foreach ($items as $item) {
                $itemSku = trim($item['sku']);
                if (isset($affectedItems[$itemSku])) {
                    unset($affectedItems[$itemSku]);
                }
            }
            $subject->setAffectedItems($affectedItems);
        }
    }
}
