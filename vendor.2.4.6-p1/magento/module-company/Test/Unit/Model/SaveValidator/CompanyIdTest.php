<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\CompanyId;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for company id validator.
 */
class CompanyIdTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var CompanyInterface|MockObject
     */
    private $initialCompany;

    /**
     * @var CompanyId
     */
    private $companyId;

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
        $this->initialCompany = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->companyId = $objectManager->getObject(
            CompanyId::class,
            [
                'company' => $this->company,
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
        $companyId = 1;
        $this->company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->initialCompany->expects($this->once())->method('getId')->willReturn($companyId);
        $this->companyId->execute();
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with companyId = 1');
        $this->company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->initialCompany->expects($this->once())->method('getId')->willReturn(null);
        $this->companyId->execute();
    }
}
