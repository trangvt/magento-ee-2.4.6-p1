<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Customer;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Controller\Customer\Manage;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ManageTest extends TestCase
{
    /**
     * @var StatusServiceInterface|MockObject
     */
    private $moduleConfig;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Manage
     */
    private $manage;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleConfig = $this->getMockForAbstractClass(StatusServiceInterface::class);
        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->authorization = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->manage = $objectManager->getObject(
            Manage::class,
            [
                'request' => $this->request,
                'moduleConfig' => $this->moduleConfig,
                'userContext' => $this->userContext,
                'logger' => $this->logger,
                'authorization' => $this->authorization,
                'resultFactory' => $this->resultFactory
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn(1);
        $result = $this->createMock(Forward::class);
        $result->expects($this->once())->method('forward')->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')->willReturn($result);

        $this->assertInstanceOf(Forward::class, $this->manage->execute());
    }
}
