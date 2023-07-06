<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Registry;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Collection $sharedCatalogCollection */
$sharedCatalogCollection = Bootstrap::getObjectManager()
    ->create(Collection::class);

$sharedCatalogCollection->addFieldToFilter('name', ['like' => '%Company%']);

foreach ($sharedCatalogCollection as $sharedCatalog) {
    try {
        $sharedCatalog->delete();
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    } catch (\Exception $e) {
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

/**
 * Reindex
 */
$appDir = dirname(\Magento\TestFramework\Helper\Bootstrap::getInstance()->getAppTempDir());
$out = '';
// phpcs:ignore Magento2.Security.InsecureFunction
exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
