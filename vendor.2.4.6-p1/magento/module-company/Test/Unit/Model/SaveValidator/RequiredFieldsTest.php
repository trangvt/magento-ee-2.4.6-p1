<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\RequiredFields;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for required fields validator.
 */
class RequiredFieldsTest extends TestCase
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
     * @var RequiredFields
     */
    private $requiredFields;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->exception = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->requiredFields = $objectManager->getObject(
            RequiredFields::class,
            [
                'company' => $this->company,
                'exception' => $this->exception,
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
        $this->company->expects($this->exactly(9))->method('getData')->withConsecutive(
            [CompanyInterface::NAME],
            [CompanyInterface::COMPANY_EMAIL],
            [CompanyInterface::STREET],
            [CompanyInterface::CITY],
            [CompanyInterface::POSTCODE],
            [CompanyInterface::TELEPHONE],
            [CompanyInterface::COUNTRY_ID],
            [CompanyInterface::SUPER_USER_ID],
            [CompanyInterface::CUSTOMER_GROUP_ID]
        )->willReturn('some value');
        $this->exception->expects($this->never())->method('addError');
        $this->requiredFields->execute();
    }

    /**
     * Test for execute with errors.
     *
     * @return void
     */
    public function testExecuteWithErrors()
    {
        $this->company->expects($this->exactly(9))->method('getData')->withConsecutive(
            [$this->equalTo(CompanyInterface::NAME)],
            [$this->equalTo(CompanyInterface::COMPANY_EMAIL)],
            [$this->equalTo(CompanyInterface::STREET)],
            [$this->equalTo(CompanyInterface::CITY)],
            [$this->equalTo(CompanyInterface::POSTCODE)],
            [$this->equalTo(CompanyInterface::TELEPHONE)],
            [$this->equalTo(CompanyInterface::COUNTRY_ID)],
            [$this->equalTo(CompanyInterface::SUPER_USER_ID)],
            [$this->equalTo(CompanyInterface::CUSTOMER_GROUP_ID)]
        )->willReturn(null);
        $errorMessagePhrase = '"%fieldName" is required. Enter and try again.';
        $this->exception->expects($this->exactly(9))->method('addError')->withConsecutive(
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::NAME]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::COMPANY_EMAIL]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::STREET]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::CITY]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::POSTCODE]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::TELEPHONE]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::COUNTRY_ID]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::SUPER_USER_ID]))],
            [$this->equalTo(__($errorMessagePhrase, ['fieldName' => CompanyInterface::CUSTOMER_GROUP_ID]))]
        )->willReturnSelf();
        $this->requiredFields->execute();
    }
}
