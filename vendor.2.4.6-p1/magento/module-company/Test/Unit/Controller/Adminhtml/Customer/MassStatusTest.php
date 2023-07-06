<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Customer;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Controller\Adminhtml\Customer\MassStatus;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\Manager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassStatusTest extends TestCase
{
    /**
     * @var MassStatus
     */
    protected $massAction;

    /**
     * @var Redirect|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Http|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var Manager|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Collection|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $customerCollectionMock;

    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $customerCollectionFactoryMock;

    /**
     * @var Filter|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $filterMock;

    /**
     * @var CustomerRepositoryInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    protected $customerRepositoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->responseMock = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->createPartialMock(
            \Magento\Framework\ObjectManager\ObjectManager::class,
            ['create']
        );
        $this->messageManagerMock = $this->createMock(Manager::class);
        $this->customerCollectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->customerCollectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $redirect = $this->createMock(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $resultRedirectFactory->expects($this->any())->method('create')->willReturn($redirect);
        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterMock = $this->createMock(Filter::class);
        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->customerCollectionMock)
            ->willReturnArgument(0);
        $this->customerCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollectionMock);
        $customer = $this->createMock(Customer::class);
        $companyAttributes =
            $this->getMockForAbstractClass(CompanyCustomerInterface::class);
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );

        $customer->expects($this->any())->method('getExtensionAttributes')->willReturn($customerExtension);
        $customerExtension->expects($this->any())->method('getCompanyAttributes')->willReturn($companyAttributes);
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['setStatus'])
            ->getMockForAbstractClass();
        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($customer);
        $this->massAction = $objectManagerHelper->getObject(
            MassStatus::class,
            [
                'filter' => $this->filterMock,
                'collectionFactory' => $this->customerCollectionFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'resultRedirectFactory' => $resultRedirectFactory,
                'messageManager' => $this->messageManagerMock
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $customersIds = [10, 11, 12];
        $this->customerCollectionMock->expects($this->any())
            ->method('getAllIds')
            ->willReturn($customersIds);

        $this->customerRepositoryMock->expects($this->any())
            ->method('setStatus')
            ->willReturnMap([[10, true], [11, true], [12, true]]);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 record(s) were updated.', count($customersIds)));

        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('customer/*/index')
            ->willReturnSelf();

        $this->massAction->execute();
    }
}
