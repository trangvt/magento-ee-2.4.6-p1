<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\CompanyStatus;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for company status validator.
 */
class CompanyStatusTest extends TestCase
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
     * @var CompanyStatus
     */
    private $companyStatus;

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
        $this->exception = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->companyStatus = $objectManager->getObject(
            CompanyStatus::class,
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
        $this->company->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_APPROVED);
        $this->exception->expects($this->never())->method('addError');
        $this->companyStatus->execute();
    }

    /**
     * Test for execute method with invalid status.
     *
     * @return void
     */
    public function testExecuteWithInvalidStatus()
    {
        $status = -1;
        $this->company->expects($this->atLeastOnce())->method('getStatus')->willReturn($status);
        $this->exception->expects($this->once())->method('addError')->with(
            __(
                'Invalid value of "%value" provided for the %fieldName field.',
                ['fieldName' => 'status', 'value' => $status]
            )
        )->willReturnSelf();
        $this->companyStatus->execute();
    }
}
