<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History\Listing\Column;

use Magento\Authorization\Model\UserContextInterface;
use Magento\CompanyCredit\Ui\Component\History\Listing\Column\UpdatedBy;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdatedByTest extends TestCase
{
    /**
     * @var UpdatedBy
     */
    private $updatedByColumn;

    /**
     * @var UserFactory|MockObject
     */
    private $userFactory;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $nameGeneration;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userFactory = $this->createPartialMock(
            UserFactory::class,
            ['create']
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->nameGeneration = $this->createMock(
            CustomerNameGenerationInterface::class
        );
        $context = $this->createMock(
            ContextInterface::class
        );
        $processor = $this->createMock(
            Processor::class
        );
        $context->expects($this->never())->method('getProcessor')->willReturn($processor);

        $objectManager = new ObjectManager($this);
        $this->updatedByColumn = $objectManager->getObject(
            UpdatedBy::class,
            [
                'context' => $context,
                'userFactory' => $this->userFactory,
                'customerRepository' => $this->customerRepository,
                'nameGeneration' => $this->nameGeneration,
            ]
        );
        $this->updatedByColumn->setData('name', 'updated_by');
    }

    /**
     * Test method for prepareDataSource.
     */
    public function testPrepareDataSourceWithUser()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['updated_by' => 1, 'user_type' => UserContextInterface::USER_TYPE_CUSTOMER],
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    ['updated_by' => 'user user', 'user_type' => UserContextInterface::USER_TYPE_CUSTOMER],
                ]
            ]
        ];

        $user = $this->createMock(
            CustomerInterface::class
        );
        $this->customerRepository->expects($this->once())->method('getById')->with(1)->willReturn($user);
        $this->nameGeneration->expects($this->once())->method('getCustomerName')->with($user)->willReturn('user user');

        $this->assertEquals($expected, $this->updatedByColumn->prepareDataSource($dataSource));
    }

    /**
     * Test method for prepareDataSource.
     */
    public function testPrepareDataSourceWithAdmin()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['updated_by' => 1, 'user_type' => UserContextInterface::USER_TYPE_ADMIN],
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    ['updated_by' => 'admin admin', 'user_type' => UserContextInterface::USER_TYPE_ADMIN],
                ]
            ]
        ];

        $user = $this->createMock(
            User::class
        );
        $this->userFactory->expects($this->once())->method('create')->willReturn($user);
        $user->expects($this->once())->method('load')->with(1)->willReturnSelf();
        $user->expects($this->once())->method('getName')->willReturn('admin admin');

        $this->assertEquals($expected, $this->updatedByColumn->prepareDataSource($dataSource));
    }
}
