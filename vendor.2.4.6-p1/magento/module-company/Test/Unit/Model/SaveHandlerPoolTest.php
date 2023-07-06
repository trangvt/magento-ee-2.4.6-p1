<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveHandlerInterface;
use Magento\Company\Model\SaveHandlerPool;
use Magento\Company\Model\SaveValidatorPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\SaveHandlerPool class.
 */
class SaveHandlerPoolTest extends TestCase
{
    /**
     * @var SaveValidatorPool
     */
    private $saveValidatorPool;

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $handler = $this->getMockBuilder(SaveHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->saveValidatorPool = $objectManager->getObject(
            SaveHandlerPool::class,
            [
                'handlers' => [$handler],
            ]
        );
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $handler->expects($this->once())->method('execute');

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
        $this->expectExceptionMessage('is not an instance of Magento\Company\Model\SaveHandlerInterface');
        $handler = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->saveValidatorPool = $objectManager->getObject(
            SaveHandlerPool::class,
            [
                'handlers' => [$handler],
            ]
        );
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->saveValidatorPool->execute($company, $company);
    }
}
