<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Customer;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Controller\Customer\Get;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTest extends TestCase
{
    /**
     * @var Get
     */
    private $get;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJson;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var AclInterface|MockObject
     */
    private $acl;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->createMock(Structure::class);
        $this->structureManager->expects($this->once())->method('getAllowedIds')->willReturn(
            ['users' => [1, 2, 5, 7]]
        );
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->acl = $this->getMockForAbstractClass(AclInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->resultJson = $this->createPartialMock(Json::class, ['setData']);
        $resultFactory->expects($this->once())->method('create')->willReturn($this->resultJson);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->get = $objectManagerHelper->getObject(
            Get::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'customerRepository' => $this->customerRepository,
                'acl' => $this->acl,
                'logger' => $logger,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @param int $customerId
     * @param MockObject $customer
     * @param ReturnStub|\PHPUnit\Framework\MockObject\Stub\Exception $customerResult
     * @param int $getCustomerInvocation
     * @param int $invocationCount
     * @param string $expect
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $customerId,
        $customer,
        $customerResult,
        $getCustomerInvocation,
        $invocationCount,
        $expect
    ) {
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $companyAttributes = $this->createMock(Customer::class);
        $this->customerRepository->expects($this->exactly($getCustomerInvocation))
            ->method('getById')->with($customerId)->will($customerResult);
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($invocationCount ? $this->atLeastOnce() : $this->never())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $customer->expects($invocationCount ? $this->atLeastOnce() : $this->never())
            ->method('getExtensionAttributes')->willReturn($customerExtension);
        $customer->expects($this->exactly($invocationCount))->method('__toArray')->willReturn([]);
        $companyAttributes->expects($this->exactly($invocationCount))->method('getJobTitle')->willReturn('job title');
        $companyAttributes->expects($this->exactly($invocationCount))->method('getTelephone')->willReturn('111111');
        $companyAttributes->expects($this->exactly($invocationCount))->method('getStatus')->willReturn('status');
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->acl->expects($this->exactly($invocationCount))
            ->method('getRolesByUserId')->with($customerId)->willReturn([$role]);
        $role->expects($this->exactly($invocationCount))->method('getId')->willReturn(9);
        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };
        $this->resultJson->expects($this->once())->method('setData')->willReturnCallback($setDataCallback);
        $this->get->execute();
        $this->assertEquals($expect, $result);
    }

    /**
     * Data provider for testExecute.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        $customer = $this->createMock(\Magento\Customer\Model\Data\Customer::class);
        return [
            [
                1,
                $customer,
                $this->returnValue($customer),
                1,
                1,
                'ok'
            ],
            [
                2,
                $customer,
                $this->throwException(new \Exception()),
                1,
                0,
                'error'
            ],
            [
                2,
                $customer,
                $this->throwException(new LocalizedException(__('phrase'))),
                1,
                0,
                'error'
            ],
            [
                4,
                $customer,
                $this->returnValue(null),
                0,
                0,
                'error'
            ],
        ];
    }
}
