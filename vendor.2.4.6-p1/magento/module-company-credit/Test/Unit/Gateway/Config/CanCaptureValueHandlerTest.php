<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Gateway\Config;

use Magento\CompanyCredit\Gateway\Config\CanCaptureValueHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for CanCaptureValueHandler.
 */
class CanCaptureValueHandlerTest extends TestCase
{

    /**
     * @var ConfigInterface|MockObject
     */
    private $configInterface;

    /**
     * @var CanCaptureValueHandler
     */
    private $canCaptureValueHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManagerHelper($this);
        $this->canCaptureValueHandler = $objectManager->getObject(
            CanCaptureValueHandler::class,
            [
                'configInterface' => $this->configInterface
            ]
        );
    }

    /**
     * Test for handle method.
     *
     * @param string $status
     * @param bool $result
     * @return void
     * @dataProvider handleDataProvider
     */
    public function testHandle($status, $result)
    {
        $subject = [];
        $this->configInterface->expects($this->once())->method('getValue')->with('order_status')->willReturn($status);

        $this->assertEquals($result, $this->canCaptureValueHandler->handle($subject));
    }

    /**
     * Data provider for testHandle method.
     *
     * @return array
     */
    public function handleDataProvider()
    {
        return [
            [Order::STATE_PROCESSING, true],
            [Order::STATE_PENDING_PAYMENT, false],
        ];
    }
}
