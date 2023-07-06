<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\CustomerGroup;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for customer group validator.
 */
class CustomerGroupTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $customerGroupRepository;

    /**
     * @var CustomerGroup
     */
    private $customerGroup;

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
        $this->customerGroupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->customerGroup = $objectManager->getObject(
            CustomerGroup::class,
            [
                'company' => $this->company,
                'customerGroupRepository' => $this->customerGroupRepository,
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
        $customerGroupId = 1;
        $this->company->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $customerGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupRepository->expects($this->once())
            ->method('getById')->with($customerGroupId)->willReturn($customerGroup);
        $this->customerGroup->execute();
    }

    /**
     * Test for execute method with non-existing customer group.
     *
     * @return void
     */
    public function testExecuteWithNonExistingCustomerGroup()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with customerGroupId = 1');
        $customerGroupId = 1;
        $this->company->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->customerGroupRepository->expects($this->once())->method('getById')->with($customerGroupId)
            ->willThrowException(new NoSuchEntityException());
        $this->customerGroup->execute();
    }
}
