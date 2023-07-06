<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Laminas\Validator\EmailAddress;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Company\Model\SaveValidator\CompanyEmail;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for company email validator.
 */
class CompanyEmailTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var InputException|MockObject
     */
    private $exception;

    /**
     * @var EmailAddress|MockObject
     */
    private $emailValidator;

    /**
     * @var CollectionFactory|MockObject
     */
    private $companyCollectionFactory;

    /**
     * @var CompanyInterface|MockObject
     */
    private $initialCompany;

    /**
     * @var CompanyEmail
     */
    private $companyEmail;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->exception = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()->getMock();
        $this->emailValidator = $this->getMockBuilder(EmailAddress::class)
            ->disableOriginalConstructor()->getMock();
        $this->companyCollectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->initialCompany = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->companyEmail = $objectManager->getObject(
            CompanyEmail::class,
            [
                'company' => $this->company,
                'exception' => $this->exception,
                'emailValidator' => $this->emailValidator,
                'companyCollectionFactory' => $this->companyCollectionFactory,
                'initialCompany' => $this->initialCompany,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $companyEmail = 'company1@example.com';
        $this->company->method('getCompanyEmail')
            ->willReturn($companyEmail);
        $this->emailValidator->method('isValid')
            ->with($companyEmail)
            ->willReturn(true);
        $this->company->method('getId')
            ->willReturn(1);
        $this->initialCompany->method('getCompanyEmail')
            ->willReturn('company2@example.com');
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCollectionFactory->method('create')
            ->willReturn($collection);
        $collection->method('addFieldToFilter')->with(
            CompanyInterface::COMPANY_EMAIL,
            $companyEmail
        )->willReturnSelf();
        $collection->method('load')
            ->willReturnSelf();
        $collection->method('getSize')
            ->willReturn(0);
        $this->exception->expects($this->never())->method('addError');
        $this->companyEmail->execute();
    }

    /**
     * Test for execute method with invalid email address.
     *
     * @return void
     */
    public function testExecuteWithInvalidEmailAddress(): void
    {
        $companyEmail = 'company1@example';
        $this->company->method('getCompanyEmail')
            ->willReturn($companyEmail);
        $this->emailValidator->method('isValid')
            ->with($companyEmail)
            ->willReturn(false);
        $this->exception->expects($this->once())
            ->method('addError')
            ->with(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'company_email', 'value' => $companyEmail]
                )
            )
            ->willReturnSelf();
        $this->companyEmail->execute();
    }

    /**
     * Test for execute method with non-unique email address.
     *
     * @return void
     */
    public function testExecuteWithNonUniqueEmailAddress(): void
    {
        $companyEmail = 'company1@example.com';
        $this->company->method('getCompanyEmail')
            ->willReturn($companyEmail);
        $this->emailValidator->method('isValid')
            ->with($companyEmail)
            ->willReturn(true);
        $this->company->method('getId')
            ->willReturn(1);
        $this->initialCompany->method('getCompanyEmail')
            ->willReturn('company2@example.com');
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCollectionFactory->method('create')
            ->willReturn($collection);
        $collection->method('addFieldToFilter')->with(
            CompanyInterface::COMPANY_EMAIL,
            $companyEmail
        )->willReturnSelf();
        $collection->method('load')
            ->willReturnSelf();
        $collection->method('getSize')
            ->willReturn(1);
        $this->exception->expects($this->once())
            ->method('addError')
            ->with(
                __(
                    'Company with this email address already exists in the system.'
                    . ' Enter a different email address to continue.'
                )
            )
            ->willReturnSelf();
        $this->companyEmail->execute();
    }

    /**
     * Checks that email unique validation is a case insensitive
     *
     * @return void
     */
    public function testExecuteWithUppercaseEmailAddress(): void
    {
        $companyEmail = 'Company2@example.com';
        $this->company->method('getCompanyEmail')
            ->willReturn($companyEmail);
        $this->emailValidator->method('isValid')
            ->with($companyEmail)
            ->willReturn(true);
        $this->company->method('getId')
            ->willReturn(1);
        $this->initialCompany->method('getCompanyEmail')
            ->willReturn('company2@example.com');
        $this->exception->expects($this->never())->method('addError');
        $this->companyEmail->execute();
    }
}
