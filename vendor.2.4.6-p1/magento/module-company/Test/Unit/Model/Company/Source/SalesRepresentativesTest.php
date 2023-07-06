<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company\Source;

use Magento\Company\Model\Company\Source\SalesRepresentatives;
use Magento\User\Model\ResourceModel\User;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesRepresentativesTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    protected $userCollectionFactory;

    /**
     * @var Collection|MockObject
     */
    protected $collection;

    /**
     * @var SalesRepresentatives
     */
    protected $salesRepresentative;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->userCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            [
                'create'
            ]
        );
        $this->collection = $this->createPartialMock(
            Collection::class,
            [
                'getItems'
            ]
        );
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $this->salesRepresentative = new SalesRepresentatives($this->userCollectionFactory);
    }

    /**
     * Test toOptionArray
     */
    public function testToOptionArray()
    {
        $id = 666;
        $userName = 'user_name';
        $result = [['label' => $userName, 'value' => $id]];
        $user = $this->getMockBuilder(User::class)
            ->addMethods(['getUserName', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->expects($this->once())->method('getItems')->willReturn([$user]);
        $user->expects($this->once())->method('getUserName')->willReturn($userName);
        $user->expects($this->once())->method('getId')->willReturn($id);
        $this->assertEquals($this->salesRepresentative->toOptionArray(), $result);
    }
}
