<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidatorInterface;
use Magento\Company\Model\SaveValidatorInterfaceFactory;
use Magento\Company\Model\SaveValidatorPool;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\SaveValidatorPool class.
 */
class SaveValidatorPoolTest extends TestCase
{
    /**
     * @var SaveValidatorInterfaceFactory|MockObject
     */
    private $saveValidatorFactory;

    /**
     * @var SaveValidatorPool
     */
    private $saveValidatorPool;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->saveValidatorFactory = $this
            ->getMockBuilder(SaveValidatorInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->saveValidatorPool = $objectManager->getObject(
            SaveValidatorPool::class,
            [
                'saveValidatorFactory' => $this->saveValidatorFactory,
                'validators' => [SaveValidatorInterface::class],
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $exception = new InputException();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validator = $this->getMockBuilder(SaveValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->saveValidatorFactory->expects($this->once())
            ->method('create')
            ->with(
                SaveValidatorInterface::class,
                [
                    'company' => $company,
                    'initialCompany' => $company,
                    'exception' => $exception
                ]
            )
            ->willReturn($validator);
        $validator->expects($this->once())->method('execute');
        $this->saveValidatorPool->execute($company, $company);
    }

    /**
     * Test execute method with InvalidArgumentException.
     *
     * @return void
     */
    public function testExecuteWithInvalidArgumentException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Type Magento\Framework\DataObject is not an instance of Magento\Company\Model\SaveValidatorInterface'
        );
        $exception = new InputException();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validator = new DataObject();
        $this->saveValidatorFactory->expects($this->once())
            ->method('create')
            ->with(
                SaveValidatorInterface::class,
                [
                    'company' => $company,
                    'initialCompany' => $company,
                    'exception' => $exception
                ]
            )
            ->willReturn($validator);

        $this->saveValidatorPool->execute($company, $company);
    }
}
