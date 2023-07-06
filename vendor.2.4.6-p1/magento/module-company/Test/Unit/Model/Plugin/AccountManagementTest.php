<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyManagement;
use Magento\Company\Model\Customer\Company;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\StructureRepository;
use Magento\Company\Plugin\Customer\Api\AccountManagement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountManagement.
 * @see \Magento\Company\Plugin\Customer\Api\AccountManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountManagementTest extends TestCase
{
    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var Collection|MockObject
     */
    private $userCollection;

    /**
     * @var CompanyInterfaceFactory|MockObject
     */
    private $companyFactory;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var Structure|MockObject
     */
    private $companyStructure;

    /**
     * @var CompanyManagement|MockObject
     */
    private $companyManagement;

    /**
     * @var AccountManagement
     */
    private $accountManagement;

    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Company|MockObject
     */
    private $customerCompanyMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $structureRepository;

    /**
     * Set up
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->request = $this->createPartialMock(
            Http::class,
            ['getPost']
        );
        $userCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->companyFactory = $this->createPartialMock(
            CompanyInterfaceFactory::class,
            ['create']
        );
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->companyStructure = $this->createMock(
            Structure::class
        );
        $this->structureRepository = $this->createMock(
            StructureRepository::class
        );
        $this->companyManagement = $this->createMock(
            CompanyManagementInterface::class
        );
        $this->userCollection = $this->createPartialMock(
            Collection::class,
            ['setPageSize', 'getFirstItem']
        );
        $this->customerCompanyMock = $this->createPartialMock(
            Company::class,
            ['createCompany']
        );
        $userCollectionFactory->expects($this->any())->method('create')->willReturn($this->userCollection);
        $this->companyEmailSender = $this->createMock(Sender::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManagerHelper = new ObjectManager($this);
        $this->accountManagement = $objectManagerHelper->getObject(
            AccountManagement::class,
            [
                'request' => $this->request,
                'userCollectionFactory' => $userCollectionFactory,
                'companyFactory' => $this->companyFactory,
                'companyRepository' => $this->companyRepository,
                'companyStructure' => $this->companyStructure,
                'companyManagement' => $this->companyManagement,
                'companyEmailSender' => $this->companyEmailSender,
                'urlBuilder' => $this->urlBuilder,
                'customerCompany' => $this->customerCompanyMock
            ]
        );
    }

    /**
     * function testAfterCreateAccount
     */
    public function testAfterCreateAccount()
    {
        $property = 'name';
        $value = 'value';
        $customerId = 666;
        $companyId = 555;
        $company = [$property => $value];

        /**
         * @var \Magento\Customer\Model\AccountManagement|MockObject $subject
         */
        $subject = $this->createMock(
            \Magento\Customer\Model\AccountManagement::class
        );
        /**
         * @var CustomerInterface|MockObject $result
         */
        $result = $this->createMock(
            CustomerInterface::class
        );
        $companyDataObject = $this->createMock(
            CompanyInterface::class
        );

        $this->request->expects($this->atLeastOnce())->method('getPost')->willReturn(['company' => $company]);
        $this->customerCompanyMock->expects($this->once())->method('createCompany')->willReturn($companyDataObject);
        $companyDataObject->expects($this->any())->method('getId')->willReturn($companyId);
        $result->expects($this->any())->method('getId')->willReturn($customerId);
        $this->assertSame($this->accountManagement->afterCreateAccount($subject, $result), $result);
    }

    /**
     * @dataProvider afterCreateAccountDataProvider
     * @param mixed $company
     * @return void
     */
    public function testAfterCreateAccountWithStringOrEmptyCompany($company): void
    {
        /** @var \Magento\Customer\Model\AccountManagement|MockObject $subject */
        $subject = $this->createMock(\Magento\Customer\Model\AccountManagement::class);
        /** @var CustomerInterface|MockObject $result */
        $result = $this->getMockForAbstractClass(CustomerInterface::class);
        $companyDataObject = $this->getMockForAbstractClass(CompanyInterface::class);

        $this->request->expects($this->atLeastOnce())->method('getPost')->willReturn($company);
        $this->customerCompanyMock
            ->expects($this->never())
            ->method('createCompany')
            ->willReturn($companyDataObject);
        $companyDataObject->expects($this->never())->method('getId');

        $this->assertSame($this->accountManagement->afterCreateAccount($subject, $result), $result);
    }

    /**
     * @return array
     */
    public function afterCreateAccountDataProvider(): array
    {
        return [
            ['company' => 'name'],
            ['company' => null],
            ['company' => []],
        ];
    }
}
