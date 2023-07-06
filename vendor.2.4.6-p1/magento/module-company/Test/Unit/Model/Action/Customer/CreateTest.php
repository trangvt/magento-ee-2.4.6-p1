<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Action\Customer;

use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Action\Customer\Create;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Action\Customer\Create class.
 */
class CreateTest extends TestCase
{
    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $customerManager;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;
    /**
     * @var object
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerManager = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Create::class,
            [
                'customerRepository' => $this->customerRepository,
                'structureManager' => $this->structureManager,
                'customerManager' => $this->customerManager,
                'objectHelper' => $objectManager,
            ]
        );
    }

    /**
     * Test method \Magento\Company\Model\Action\Customer\Create::execute.
     *
     * @param int|null $customerId
     * @param int $customerRepositorySaveCallsAmount
     * @param int $customerManagerCreateAccountCallsAmount
     * @return void
     *
     * @dataProvider createDataProvider
     */
    public function testExecute(
        $customerId,
        $customerRepositorySaveCallsAmount,
        $customerManagerCreateAccountCallsAmount
    ) {
        $email = 'sample@example.com';

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $structure = $this->getMockBuilder(StructureInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $structure->expects($this->once())->method('getId')->willReturn(1);

        $this->customerManager->expects($this->exactly($customerManagerCreateAccountCallsAmount))
            ->method('createAccount');

        $this->customerRepository->expects($this->exactly($customerRepositorySaveCallsAmount))->method('save');
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $customer->expects($this->once())->method('getEmail')->willReturn($email);
        $this->customerRepository->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($customer);

        $this->structureManager->expects($this->once())->method('getStructureByCustomerId')->willReturn($structure);
        $this->structureManager->expects($this->once())->method('removeCustomerNode');

        $this->model->execute($customer, 1);
    }

    /**
     * Data provider for "testExecute" method.
     *
     * @return array
     */
    public function createDataProvider()
    {
        return [
            [1, 1, 0],
            [null, 0, 1]
        ];
    }
}
