<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History;

use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Model\CreditDetails\CustomerProvider;
use Magento\CompanyCredit\Model\ResourceModel\History\Collection;
use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory;
use Magento\CompanyCredit\Ui\Component\History\BalanceDataProvider as SystemUnderTest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BalanceDataProviderTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var SystemUnderTest
     */
    private $dataProvider;

    /**
     * @var CustomerProvider|MockObject
     */
    private $customerProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->customerProvider = $this->collection = $this->createMock(
            CustomerProvider::class
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
                'collectionFactory' => $collectionFactory,
                'customerProvider' => $this->customerProvider
            ]
        );
    }

    /**
     * Test for getData method.
     *
     * @return void
     */
    public function testGetData()
    {
        $currentUserCredit = $this->getMockForAbstractClass(CreditDataInterface::class);
        $userCreditInvocation = 2;
        $creditId = 1;
        $result = ['collection data'];

        $this->request->expects($this->never())->method('getParam');
        $this->customerProvider->expects($this->exactly($userCreditInvocation))->method('getCurrentUserCredit')
            ->willReturn($currentUserCredit);
        $currentUserCredit->expects($this->once())->method('getId')->willReturn($creditId);
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with('main_table.company_credit_id', ['eq' => $creditId])->willReturnSelf();
        $this->collection->expects($this->once())->method('toArray')->willReturn($result);
        $this->assertEquals($result, $this->dataProvider->getData());
    }

    /**
     * Test for getData method with unknown user.
     *
     * @return void
     */
    public function testGetDataForUnknownUser()
    {
        $currentUserCredit = null;
        $userCreditInvocation = 1;
        $creditId = 0;
        $result = ['collection data'];

        $this->request->expects($this->never())->method('getParam');
        $this->customerProvider->expects($this->exactly($userCreditInvocation))->method('getCurrentUserCredit')
            ->willReturn($currentUserCredit);
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with('main_table.company_credit_id', ['eq' => $creditId])->willReturnSelf();
        $this->collection->expects($this->once())->method('toArray')->willReturn($result);
        $this->assertEquals($result, $this->dataProvider->getData());
    }
}
