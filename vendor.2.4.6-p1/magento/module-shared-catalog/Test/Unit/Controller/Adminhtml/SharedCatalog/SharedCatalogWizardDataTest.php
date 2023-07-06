<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\SharedCatalogWizardData;
use Magento\SharedCatalog\Model\SharedCatalog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for class SharedCatalogWizardData.
 */
class SharedCatalogWizardDataTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var SharedCatalogWizardData
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->builder = $objectManager->getObject(
            SharedCatalogWizardData::class,
            [
                'request' => $this->request,
            ]
        );
    }

    /**
     * Test method populateDataFromRequest.
     *
     * @param int|null $sharedCatalogId
     * @param int $setIdCounter
     * @return void
     * @dataProvider populateDataFromRequestDataProvider
     */
    public function testPopulateDataFromRequest($sharedCatalogId, $setIdCounter)
    {
        $sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['catalog_details'], ['shared_catalog_id'])
            ->willReturnOnConsecutiveCalls(['name' => 'test'], $sharedCatalogId);
        $sharedCatalog->expects($this->once())->method('setData');
        $sharedCatalog->expects($this->exactly($setIdCounter))->method('setId');
        $this->builder->populateDataFromRequest($sharedCatalog);
    }

    /**
     * Data provider for populateDataFromRequest method.
     *
     * @return array
     */
    public function populateDataFromRequestDataProvider()
    {
        return [
            [2, 1],
            [null, 0]
        ];
    }

    /**
     * Test method populateDataFromRequest with exception.
     *
     * @return void
     */
    public function testPopulateDataFromRequestWithException()
    {
        $this->expectException('UnexpectedValueException');
        $sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('catalog_details')
            ->willReturn(['test']);
        $sharedCatalog->expects($this->never())->method('setData');
        $sharedCatalog->expects($this->never())->method('setId');
        $this->builder->populateDataFromRequest($sharedCatalog);
    }
}
