<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\Indexer;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ProductRepositoryFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class BaseFinalPricePluginTest extends TestCase
{
    public const DIRECT_PRODUCTS_PRICE_ASSIGNING = 'btob/website_configuration/direct_products_price_assigning';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var array
     */
    private $defaultConfig = [];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * Connection adapter
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connectionMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->repositoryFactory = $objectManager->get(ProductRepositoryFactory::class);
        $this->productManagement = $objectManager->create(ProductManagementInterface::class);
        $this->storeManager = $objectManager->get(StoreManagerInterface::class);
        $this->scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $this->mutableScopeConfig = $objectManager->get(MutableScopeConfigInterface::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
        $this->connectionMock = $this->resource->getConnection();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture btob/website_configuration/sharedcatalog_active 1
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testAfterGetQuery()
    {
        $this->setConfig(
            self::DIRECT_PRODUCTS_PRICE_ASSIGNING,
            (string) 1,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getCode()
        );
        /** @var ProductRepository $repository */
        $repository = $this->repositoryFactory->create();
        $product = $repository->get('simple');
        $sharedCatalog = $this->getTestFixture();
        $this->productManagement->assignProducts($sharedCatalog->getId(), [$product]);
        $product->save();
        $select = $this->connectionMock->select()->from($this->resource->getTableName('shared_catalog_product_item'));
        $sharedCatalogProductItemCount = count($this->connectionMock->fetchAll($select));
        $select = $this->connectionMock->select()->from($this->resource->getTableName('catalog_product_index_price'));
        $catalogProductIndexPriceCount = count($this->connectionMock->fetchAll($select));
        $this->assertEquals($sharedCatalogProductItemCount, $catalogProductIndexPriceCount);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture btob/website_configuration/sharedcatalog_active 1
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoDataFixture Magento/CatalogRule/_files/simple_products.php
     */
    public function testPriceIndexerTableWithAndWithoutSharedCatalogAssignment()
    {
        $this->setConfig(
            self::DIRECT_PRODUCTS_PRICE_ASSIGNING,
            (string) 1,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getCode()
        );
        /** @var ProductRepository $repository */
        $repository = $this->repositoryFactory->create();
        $product1 = $repository->get('simple1');
        $sharedCatalog = $this->getTestFixture();
        $this->productManagement->assignProducts($sharedCatalog->getId(), [$product1]);
        $product1->save();
        $select = $this->connectionMock->select()->from(
            $this->resource->getTableName('catalog_product_index_price'),
            'entity_id'
        )->where('entity_id = ?', $product1->getEntityId());
        $quoteRow = $this->connectionMock->fetchRow($select);
        $this->assertEquals($product1->getEntityId(), (string)$quoteRow['entity_id']);
        $product2 = $repository->get('simple2');
        $product2->save();
        $select = $this->connectionMock->select()->from(
            $this->resource->getTableName('catalog_product_index_price'),
            'entity_id'
        )->where('entity_id = ?', $product2->getEntityId());
        $this->assertFalse($this->connectionMock->fetchRow($select));
    }

    /**
     * @param string $path
     * @param string|null $value
     * @param string $scopeType
     * @param string|null $scopeId
     */
    private function setConfig(
        string $path,
        ?string $value,
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        ?string $scopeId = null
    ): void {
        if (!array_key_exists($path, $this->defaultConfig)
            || !array_key_exists($scopeType, $this->defaultConfig[$path])
            || !array_key_exists($scopeId, $this->defaultConfig[$path][$scopeType])
        ) {
            $this->defaultConfig[$path][$scopeType][$scopeId] = $this->scopeConfig->getValue(
                $path,
                $scopeType,
                $scopeId
            );
        }
        $this->mutableScopeConfig->setValue($path, $value, $scopeType, $scopeId);
    }

    /**
     * Gets Shared Catalog Fixture.
     *
     * @return SharedCatalog
     */
    private function getTestFixture(): SharedCatalog
    {
        /** @var Collection $sharedCatalogCollection */
        $sharedCatalogCollection = Bootstrap::getObjectManager()->create(Collection::class);
        return $sharedCatalogCollection->getLastItem();
    }
}
