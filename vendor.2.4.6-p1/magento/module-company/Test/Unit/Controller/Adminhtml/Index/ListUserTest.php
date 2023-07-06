<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Company\Controller\Adminhtml\Index\ListUser;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ListUserTest extends TestCase
{
    /**
     * @var ListUser
     */
    private $list;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $request;

    /**
     * @var Collection|\PHPUnit\Framework\MockObject_MockObject
     */
    private $customerCollection;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $response = $this->createPartialMock(
            Raw::class,
            [
                'setHeader',
                'setContents'
            ]
        );
        $resultRawFactory = $this->createPartialMock(
            RawFactory::class,
            ['create']
        );
        $resultRawFactory->expects($this->once())->method('create')->willReturn($response);
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $this->customerCollection = $this->createPartialMock(
            Collection::class,
            ['load', 'getIdFieldName', 'addFieldToFilter']
        );
        $this->customerCollection->expects($this->any())
            ->method('getIdFieldName')->willReturn('id');
        $customerCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $customerCollectionFactory->expects($this->any())
            ->method('create')->willReturn($this->customerCollection);

        $objectManagerHelper = new ObjectManager($this);
        $this->list = $objectManagerHelper->getObject(
            ListUser::class,
            [
                'resultRawFactory' => $resultRawFactory,
                'customerCollectionFactory' => $customerCollectionFactory,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->request->expects($this->once())->method('getParam')->with('email')->willReturn('example@test');
        $this->customerCollection->expects($this->once())
            ->method('addFieldToFilter')->with(
                'email',
                [
                    'like' => 'example@test%'
                ]
            )->willReturnSelf();
        $item = new DataObject(['email' => 'example@test.com', 'id' => 1]);
        $this->customerCollection->addItem($item);

        $this->assertInstanceOf(Raw::class, $this->list->execute());
    }

    /**
     * Test execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $this->request->expects($this->once())->method('getParam')->with('email')->willThrowException($exception);

        $this->assertInstanceOf(Raw::class, $this->list->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception'));
        $exception = new LocalizedException($phrase);
        $this->request->expects($this->once())->method('getParam')->with('email')->willThrowException($exception);

        $this->assertInstanceOf(Raw::class, $this->list->execute());
    }
}
