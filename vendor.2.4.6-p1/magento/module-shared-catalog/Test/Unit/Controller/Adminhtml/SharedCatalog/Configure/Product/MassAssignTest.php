<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\MassAssign;
use Magento\SharedCatalog\Model\Form\Storage\SharedCatalogMassAssignment;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for controller Adminhtml\SharedCatalog\Configure\Product\MassAssignTest.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassAssignTest extends TestCase
{
    /**
     * @var MassAssign
     */
    private $massAssign;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var SharedCatalogMassAssignment|MockObject
     */
    private $sharedCatalogMassAssignment;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sharedCatalogMassAssignment = $this->getMockBuilder(SharedCatalogMassAssignment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->massAssign = $objectManagerHelper->getObject(
            MassAssign::class,
            [
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'sharedCatalogMassAssignment' => $this->sharedCatalogMassAssignment,
                'filter' => $this->filter,
                'collectionFactory' => $this->collectionFactory,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'resultJsonFactory' => $this->resultJsonFactory,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Unit test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $configureKey = UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY;
        $isAssign = true;
        $sharedCatalogId = 256;
        $collection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $filteredCollection = $this
            ->getMockBuilder(AbstractCollection::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filter->expects($this->atLeastOnce())->method('getCollection')->willReturn($filteredCollection);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnOnConsecutiveCalls($configureKey, $isAssign, $sharedCatalogId);
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->atLeastOnce())->method('create')->willReturn($storage);
        $this->sharedCatalogMassAssignment->expects($this->atLeastOnce())->method('assign')
            ->with($filteredCollection, $storage, $sharedCatalogId, (int)$isAssign);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->atLeastOnce())->method('setJsonData')->willReturnSelf();
        $this->resultJsonFactory->expects($this->atLeastOnce())->method('create')->willReturn($resultJson);

        $this->assertEquals($resultJson, $this->massAssign->execute());
    }
}
