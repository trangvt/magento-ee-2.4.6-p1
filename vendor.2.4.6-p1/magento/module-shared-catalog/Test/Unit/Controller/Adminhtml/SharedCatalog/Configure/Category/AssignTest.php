<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Category\Assign;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Assign category controller unit test.
 */
class AssignTest extends TestCase
{
    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepository;

    /**
     * @var SharedCatalogAssignment|MockObject
     */
    private $sharedCatalogAssignment;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Assign
     */
    private $controller;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepository = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogAssignment = $this
            ->getMockBuilder(SharedCatalogAssignment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Assign::class,
            [
                '_request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'categoryRepository' => $this->categoryRepository,
                'sharedCatalogAssignment' => $this->sharedCatalogAssignment,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $configureKey = 'configure_key_value';
        $categoryId = 1;
        $childrenCategoriesIds = [2, 3];
        $productsToAssign = [
            'skus' => ['SKU1', 'SKU2'],
            'category_ids' => [3, 4, 5],
        ];
        $isAssign = 1;
        $isGeneralAction = 0;
        $this->request->expects($this->exactly(4))->method('getParam')
            ->withConsecutive(['configure_key'], ['category_id'], ['is_assign'], ['is_include_subcategories'])
            ->willReturnOnConsecutiveCalls($configureKey, $categoryId, $isAssign, $isGeneralAction);
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $configureKey])->willReturn($storage);
        $category = $this->getMockBuilder(CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())->method('get')->with($categoryId)->willReturn($category);
        $category->expects($this->once())->method('getAllChildren')->with(true)->willReturn($childrenCategoriesIds);
        $this->sharedCatalogAssignment->expects($this->once())->method('getAssignProductsByCategoryIds')
            ->with(array_merge($childrenCategoriesIds, [$categoryId]))->willReturn($productsToAssign);
        $storage->expects($this->once())->method('assignProducts')->with($productsToAssign['skus']);
        $storage->expects($this->once())->method('assignCategories')
            ->with(array_unique(array_merge($childrenCategoriesIds, [$categoryId], $productsToAssign['category_ids'])));
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setJsonData')->with(
            json_encode(
                [
                    'data' => [
                        'status' => 1,
                        'category' => $categoryId,
                        'is_assign' => $isAssign
                    ]
                ]
            )
        )->willReturnSelf();
        $this->assertEquals($result, $this->controller->execute());
    }

    /**
     * Test for execute method with unassign action.
     *
     * @return void
     */
    public function testExecuteUnassignAction()
    {
        $configureKey = 'configure_key_value';
        $categoryId = 2;
        $assignedCategoriesIds = [1, 2, 3];
        $productSkus = ['SKU1', 'SKU2'];
        $isAssign = 0;
        $isGeneralAction = 0;
        $this->request->expects($this->exactly(4))->method('getParam')
            ->withConsecutive(['configure_key'], ['category_id'], ['is_assign'])
            ->willReturnOnConsecutiveCalls($configureKey, $categoryId, $isAssign, $isGeneralAction);
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $configureKey])->willReturn($storage);
        $storage->expects($this->once())->method('getAssignedCategoriesIds')->willReturn($assignedCategoriesIds);
        $this->sharedCatalogAssignment->expects($this->once())->method('getProductSkusToUnassign')
            ->with([$categoryId], array_diff($assignedCategoriesIds, [$categoryId]))->willReturn($productSkus);
        $storage->expects($this->once())->method('unassignProducts')->with($productSkus);
        $storage->expects($this->once())->method('unassignCategories')->with([$categoryId]);
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setJsonData')->with(
            json_encode(
                [
                    'data' => [
                        'status' => 1,
                        'category' => $categoryId,
                        'is_assign' => $isAssign
                    ]
                ]
            )
        )->willReturnSelf();
        $this->assertEquals($result, $this->controller->execute());
    }
}
