<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Company\MassAssign;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\MassAssign\Validate;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\Company;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\CompanyFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\MassAssign\Validate.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends TestCase
{
    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $catalogManagement;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var CompanyFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Validate
     */
    private $validate;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->catalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this
            ->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory = $this
            ->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->validate = $objectManager->getObject(
            Validate::class,
            [
                'catalogManagement' => $this->catalogManagement,
                'logger' => $this->logger,
                'filter' => $this->filter,
                'collectionFactory' => $this->collectionFactory,
                'resultJsonFactory' => $this->resultJsonFactory,
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
        $collection = $this
            ->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->filter->expects($this->once())->method('getCollection')->with($collection)->willReturn($collection);
        $sharedCatalog = $this
            ->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($sharedCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getSharedCatalogId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$company]));
        $sharedCatalog->expects($this->once())->method('getId')->willReturn(1);
        $company->expects($this->once())->method('getSharedCatalogId')->willReturn(2);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($resultJson);
        $resultJson->expects($this->once())->method('setJsonData')->with(
            json_encode(['is_custom_assigned' => true], JSON_NUMERIC_CHECK)
        )->willReturnSelf();
        $this->assertEquals($resultJson, $this->validate->execute());
    }
}
