<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Tree;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Tree\Get;
use Magento\SharedCatalog\Model\Configure\Category\Tree;
use Magento\SharedCatalog\Model\Configure\Category\Tree\Renderer;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for controller Adminhtml\SharedCatalog\Configure\Tree\Get.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTest extends TestCase
{
    /**
     * @var Tree|MockObject
     */
    private $categoryTree;

    /**
     * @var Renderer|MockObject
     */
    private $categoryTreeRenderer;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var Json|MockObject
     */
    protected $resultJson;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Get
     */
    private $get;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $mapForGetParamMethod = [
            [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY, null, '3w4634dfgser'],
            ['filters', null, []]
        ];
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturnMap($mapForGetParamMethod);
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryTree = $this->getMockBuilder(Tree::class)
            ->setMethods(['getCategoryRootNode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryTreeRenderer = $this
            ->getMockBuilder(Renderer::class)
            ->setMethods(['render'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory =
            $this->createPartialMock(JsonFactory::class, ['create']);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->get = $this->objectManagerHelper->getObject(
            Get::class,
            [
                '_request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'categoryTree' => $this->categoryTree,
                'treeRenderer' => $this->categoryTreeRenderer,
                'storeManager'=> $this->storeManager,
                'wizardStorageFactory' => $this->wizardStorageFactory
            ]
        );
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $dataValue = 'sample data value';
        $data = ['data' => $dataValue];
        $storeId = 1;

        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $group = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wizardStorageFactory->expects($this->exactly(1))->method('create')->willReturn($storage);
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')->willReturn($group);
        $group->expects($this->atLeastOnce())->method('getId')->willReturn($storeId);
        $storage->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $categoryRootNode = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryTree->expects($this->exactly(1))->method('getCategoryRootNode')->willReturn($categoryRootNode);

        $this->categoryTreeRenderer->expects($this->once())
            ->method('render')
            ->with($categoryRootNode)
            ->willReturn($dataValue);
        $this->createJsonResponse($data);
        $result = $this->get->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * @param array $data
     * @return void
     */
    private function createJsonResponse(array $data)
    {
        $this->resultJson = $this->createPartialMock(Json::class, ['setJsonData']);
        $this->resultJson->expects($this->once())
            ->method('setJsonData')
            ->with(json_encode($data, JSON_NUMERIC_CHECK));
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJson);
    }
}
