<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\Assign;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\Price\ProductTierPriceLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for product assign controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AssignTest extends TestCase
{
    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var ProductTierPriceLoader|MockObject
     */
    private $productTierPriceLoader;

    /**
     * @var Assign
     */
    private $assign;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->wizardStorageFactory = $this->getMockBuilder(
            WizardFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productRepository = $this->getMockBuilder(
            ProductRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productTierPriceLoader = $this
            ->getMockBuilder(ProductTierPriceLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultJsonFactory = $this->getMockBuilder(
            JsonFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->assign = $objectManager->getObject(
            Assign::class,
            [
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'productRepository' => $this->productRepository,
                'productTierPriceLoader' => $this->productTierPriceLoader,
                'request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @param bool $isAssign
     * @param int $assignInvocationsCount
     * @param int $unassignInvocationsCount
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $isAssign,
        $assignInvocationsCount,
        $unassignInvocationsCount
    ) {
        $configurationKey = 'configuration_key';
        $productId = 1;
        $productSku = 'ProductSKU';
        $categoryIds = [2, 3];
        $sharedCatalogId = 4;
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['configure_key'], ['product_id'], ['is_assign'], ['shared_catalog_id'])
            ->willReturnOnConsecutiveCalls($configurationKey, $productId, $isAssign, $sharedCatalogId);
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds'])
            ->getMockForAbstractClass();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => $configurationKey])
            ->willReturn($storage);
        $this->productRepository->expects($this->once())
            ->method('getById')->with($productId)->willReturn($product);
        $product->expects($this->exactly($assignInvocationsCount))->method('getCategoryIds')->willReturn($categoryIds);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $storage->expects($this->exactly($assignInvocationsCount))
            ->method('assignProducts')->with([$productSku])->willReturnSelf();
        $storage->expects($this->exactly($assignInvocationsCount))
            ->method('assignCategories')->with($categoryIds)->willReturnSelf();
        $storage->expects($this->exactly($unassignInvocationsCount))
            ->method('unassignProducts')->with([$productSku])->willReturnSelf();
        $this->productTierPriceLoader->expects($this->once())
            ->method('populateProductTierPrices')
            ->with([$product], $sharedCatalogId, $storage)
            ->willReturnSelf();

        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($result);
        $storage->expects($this->once())->method('isProductAssigned')->with($productSku)->willReturn($isAssign);
        $result->expects($this->once())->method('setJsonData')->with(
            json_encode(
                [
                    'data'  => [
                        'status' => 1,
                        'product' => $productId,
                        'is_assign' => $isAssign
                    ]
                ]
            )
        )->willReturnSelf();
        $this->assertEquals($result, $this->assign->execute());
    }

    /**
     * Data provider for testExecute.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [true, 1, 0],
            [false, 0, 1]
        ];
    }
}
