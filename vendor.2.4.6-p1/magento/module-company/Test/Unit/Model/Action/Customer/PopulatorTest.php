<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Action\Customer;

use Magento\Company\Model\Action\Customer\Populator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Action\Customer\Populator class.
 */
class PopulatorTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    private $customerFactory;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $objectHelper;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Populator
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerFactory = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->objectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Populator::class,
            [
                'customerRepository' => $this->customerRepository,
                'customerFactory' => $this->customerFactory,
                'objectHelper' => $this->objectHelper,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test populate method.
     *
     * @param int|null $customerId
     * @param int $websiteId
     * @param int $storeId
     * @param array $data
     * @param array|null $customAttributes
     * @return void
     *
     * @throws LocalizedException
     * @dataProvider createDataProvider
     */
    public function testPopulate(?int $customerId, int $websiteId, int $storeId, array $data, ?array $customAttributes = []): void
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerFactory->expects($this->once())->method('create')->willReturn($customer);
        $customer->expects($this->once())->method('getId')->willReturn($customerId);
        $this->objectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with($customer, $data, CustomerInterface::class)
            ->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($website);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $website->expects($this->once())->method('getId')->willReturn($websiteId);
        $store->expects($this->once())->method('getId')->willReturn($storeId);
        $customer->expects($this->any())->method('setCustomAttributes')->with($customAttributes)->willReturn(null);
        $customer->expects($this->once())->method('setWebsiteId')->willReturn($customerId);
        $customer->expects($this->once())->method('setStoreId')->with($storeId)->willReturn($customerId);
        $customer->expects($this->once())->method('setId')->with($customerId)->willReturnSelf();

        $this->model->populate($data);
    }

    /**
     * Data provider for "testPopulate" method.
     *
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            "valid customer with id without custom customer attribute" => [1, 1, 1, [
                    'firstname' => 'CustomerFirst',
                    'lastname' => 'CustomerLast',
                ], []
            ],
            "valid customer without id without custom customer attribute" => [null, 1, 1, [],[]],
            "valid customer with id with custom customer attribute" => [1, 1, 1, [
                'firstname' => 'CustomerFirst',
                'lastname' => 'CustomerLast',
                'custom_test_attribute' => 'customer_account_create-CustomAttribute'
            ],['CustomAttribute' => 'custom_attribute']
            ],
            "valid customer without id with custom customer attribute" => [null, 1, 1, [
                'custom_test_attribute' => 'customer_account_edit-CustomAttribute'
            ],['CustomAttribute' => 'custom_attribute']
            ]
        ];
    }
}
