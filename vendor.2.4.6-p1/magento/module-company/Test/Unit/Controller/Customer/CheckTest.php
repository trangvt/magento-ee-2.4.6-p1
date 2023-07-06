<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Customer;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Controller\Customer\Check;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckTest extends TestCase
{
    /**
     * @var Check
     */
    private $check;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Json|MockObject
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
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->createMock(Structure::class);
        $this->structureManager->expects($this->any())
            ->method('getAllowedIds')->willReturn(
                ['users' => [1, 2, 5, 7]]
            );
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );
        $resultFactory->expects($this->any())
            ->method('create')->willReturn($this->resultJson);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $companyAttributes = $this->getMockForAbstractClass(CompanyCustomerInterface::class);

        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(1);
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customer->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getById')
            ->willReturn($customer);

        $this->request->expects($this->once())->method('getParam')->with('email')->willReturn('test@test.com');

        $objectManagerHelper = new ObjectManager($this);
        $this->check = $objectManagerHelper->getObject(
            Check::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'customerRepository' => $this->customerRepository,
                'logger' => $logger,
                '_request' => $this->request
            ]
        );
    }

    /**
     * function testExecuteEmptyUser.
     *
     * @return void
     */
    public function testExecuteEmptyUser()
    {
        $this->customerRepository->expects($this->any())->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'ok',
                'message' => '',
                'data' => []
            ]
        );
        $this->check->execute();
    }

    /**
     * function testExecuteLocalizedException.
     *
     * @return void
     */
    public function testExecuteLocalizedException()
    {
        $phrase = new Phrase('test');
        $this->customerRepository->expects($this->any())->method('get')
            ->willThrowException(new LocalizedException($phrase));

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'error',
                'message' => 'test',
                'data' => []
            ]
        );
        $this->check->execute();
    }

    /**
     * function testExecuteException.
     *
     * @return void
     */
    public function testExecuteException()
    {
        $this->customerRepository->expects($this->any())->method('get')
            ->willThrowException(new \Exception());

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'error',
                'message' => 'Something went wrong.',
                'data' => []
            ]
        );
        $this->check->execute();
    }

    /**
     * function testExecuteWithErrorCustomerExist.
     *
     * @return void
     */
    public function testExecuteWithErrorCustomerExist()
    {
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(2);

        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $customer = $this->createMock(
            CustomerInterface::class
        );
        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customer->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('get')
            ->willReturn($customer);

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'error',
                'message' => 'A user with this email address already exists in the system. '
                    . 'Enter a different email address to create this user.',
                'data' => []
            ]
        );
        $this->check->execute();
    }

    /**
     * function testExecuteWithErrorCustomerInCompany.
     *
     * @return void
     */
    public function testExecuteWithErrorCustomerInCompany()
    {
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(1);

        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $customer = $this->createMock(
            CustomerInterface::class
        );
        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customer->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('get')
            ->willReturn($customer);

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'error',
                'message' => 'A user with this email address is already a member of your company.',
                'data' => []
            ]
        );
        $this->check->execute();
    }

    /**
     * function testExecuteWithErrorCustomerFree.
     *
     * @return void
     */
    public function testExecuteWithErrorCustomerFree()
    {
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(0);
        $companyAttributes->expects($this->any())->method('getJobTitle')
            ->willReturn('job');
        $companyAttributes->expects($this->any())->method('getTelephone')
            ->willReturn('111');
        $companyAttributes->expects($this->any())->method('getStatus')
            ->willReturn('1');

        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $customer = $this->createMock(
            CustomerInterface::class
        );
        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customer->expects($this->any())->method('getFirstname')->willReturn('first');
        $customer->expects($this->any())->method('getLastname')->willReturn('last');
        $customer->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('get')
            ->willReturn($customer);

        $this->resultJson->expects($this->once())->method('setData')->willReturn(
            [
                'status' => 'ok',
                'message' => 'A user with this email address already exists in the system. '
                    . 'If you proceed, the user will be linked to your company.',
                'data' => [
                    'firstname' => 'first',
                    'lastname' => 'last',
                    'customer[jobtitle]' => 'job',
                    'customer[telephone]' => '111',
                    'customer[status]' => '1',
                ]
            ]
        );
        $this->check->execute();
    }
}
