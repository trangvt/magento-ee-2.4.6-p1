<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\SalesRepresentative;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for sales representative validator.
 */
class SalesRepresentativeTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var CollectionFactory|MockObject
     */
    private $userCollectionFactory;

    /**
     * @var SalesRepresentative
     */
    private $salesRepresentative;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userCollectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->salesRepresentative = $objectManager->getObject(
            SalesRepresentative::class,
            [
                'company' => $this->company,
                'userCollectionFactory' => $this->userCollectionFactory,
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
        $salesRepresentativeId = 1;
        $this->company->expects($this->atLeastOnce())
            ->method('getSalesRepresentativeId')->willReturn($salesRepresentativeId);
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->once())
            ->method('addFieldToFilter')->with('main_table.user_id', $salesRepresentativeId)->willReturnSelf();
        $collection->expects($this->once())->method('load')->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn(1);
        $this->salesRepresentative->execute();
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     * #expectedExceptionMessage No such entity with salesRepresentativeId = 1
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $salesRepresentativeId = 1;
        $this->company->expects($this->atLeastOnce())
            ->method('getSalesRepresentativeId')->willReturn($salesRepresentativeId);
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->once())
            ->method('addFieldToFilter')->with('main_table.user_id', $salesRepresentativeId)->willReturnSelf();
        $collection->expects($this->once())->method('load')->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn(0);
        $this->salesRepresentative->execute();
    }
}
