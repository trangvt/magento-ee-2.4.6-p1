<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History;

use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Model\HistoryFactory;
use Magento\CompanyCredit\Model\ResourceModel\History\Collection;
use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory;
use Magento\CompanyCredit\Ui\Component\History\DataProvider as SystemUnderTest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var HistoryFactory|MockObject
     */
    private $historyFactory;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var SystemUnderTest
     */
    private $dataProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->historyFactory = $this->createPartialMock(
            HistoryFactory::class,
            ['create']
        );
        $this->collection = $this->createMock(
            Collection::class
        );
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            SystemUnderTest::class,
            [
                'request' => $this->request,
                'creditDataProvider' => $this->creditDataProvider,
                'historyFactory' => $this->historyFactory,
                'collectionFactory' => $collectionFactory,
            ]
        );
    }

    /**
     * Test for getData method.
     *
     * @param int|null $companyId
     * @param int $creditId
     * @param int $paramInvocations
     * @param int $creditInvocations
     * @return void
     * @dataProvider getDataDataProvider
     */
    public function testGetData($companyId, $creditId, $paramInvocations, $creditInvocations)
    {
        $result = ['collection data'];
        $this->request->expects($this->exactly($paramInvocations))
            ->method('getParam')->with('id')->willReturn($companyId);
        $creditData = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataProvider->expects($this->exactly($creditInvocations))
            ->method('get')->with($companyId)->willReturn($creditData);
        $creditData->expects($this->exactly($creditInvocations))->method('getId')->willReturn($creditId);
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with('main_table.company_credit_id', ['eq' => $creditId])->willReturnSelf();
        $this->collection->expects($this->once())->method('toArray')->willReturn($result);
        $this->assertEquals($result, $this->dataProvider->getData());
    }

    /**
     * Data provider for testGetData.
     *
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [1, 2, 2, 1],
            [null, 0, 1, 0],
        ];
    }
}
