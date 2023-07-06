<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var QuoteIdMaskFactory $quoteIdMaskFactory */
$quoteIdMaskFactory = $objectManager->get(QuoteIdMaskFactory::class);

$quoteCollection = $objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
foreach ($quoteCollection as $quote) {
    $quote->delete();
    $quoteIdMask = $quoteIdMaskFactory->create();
    $quoteIdMask->setQuoteId($quote->getId())->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
