<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\RejectedFields;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for rejected fields validator.
 */
class RejectedFieldsTest extends TestCase
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
     * @var CompanyInterface|MockObject
     */
    private $initialCompany;

    /**
     * @var RejectedFields
     */
    private $rejectedFields;

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
        $this->initialCompany = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->rejectedFields = $objectManager->getObject(
            RejectedFields::class,
            [
                'company' => $this->company,
                'exception' => $this->exception,
                'initialCompany' => $this->initialCompany,
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
        $rejectedAt = '2017-03-17 17:32:58';
        $rejectReason = 'Lorem ipsum dolor sit amet';
        $this->company->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $this->initialCompany->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $this->company->expects($this->any())->method('getRejectReason')->willReturn($rejectReason);
        $this->initialCompany->expects($this->any())->method('getRejectReason')->willReturn($rejectReason);
        $this->exception->expects($this->never())->method('addError');
        $this->rejectedFields->execute();
    }

    /**
     * Test for execute method without rejectedAt and rejectReason
     *
     * @return void
     */
    public function testExecuteWithoutRejectedReasonAndDateTime()
    {
        $rejectedAt = null;
        $rejectReason = null;
        $this->company->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $this->company->expects($this->any())->method('getRejectReason')->willReturn($rejectReason);
        $this->exception->expects($this->never())->method('addError');
        $this->rejectedFields->execute();
    }

    /**
     * Test for execute method with error.
     *
     * @return void
     */
    public function testExecuteWithError()
    {
        $rejectedAt = '2017-03-17 17:32:58';
        $rejectReason = 'Lorem ipsum dolor sit amet';
        $this->company->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $this->initialCompany->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $this->company->expects($this->any())->method('getRejectReason')->willReturn($rejectReason);
        $this->initialCompany->expects($this->any())->method('getRejectReason')->willReturn('Some reject reason');
        $this->company->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_REJECTED);
        $this->initialCompany->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_REJECTED);
        $this->exception->expects($this->once())->method('addError')->with(
            __(
                'Invalid attribute value. Rejected date&time and Rejected Reason can be changed only'
                . ' when a company status is changed to Rejected.'
            )
        )->willReturnSelf();
        $this->rejectedFields->execute();
    }
}
