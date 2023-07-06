<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Controller\Adminhtml;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\StatusServiceInterface;
use Magento\CompanyCredit\Controller\AbstractAction;
use Magento\CompanyCredit\Controller\History\Index as HistoryIndexAction;
use Magento\CompanyCredit\Model\PaymentMethodStatus;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractActionTest extends TestCase
{
    /**
     * @var StatusServiceInterface|MockObject
     */
    protected $moduleConfig;

    /**
     * @var UserContextInterface|MockObject
     */
    protected $userContext;

    /**
     * @var PaymentMethodStatus|MockObject
     */
    protected $paymentMethodStatus;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var ActionFlag|MockObject
     */
    private $actionFlag;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirect;

    /**
     * @var \Magento\Company\Controller\Index\Index
     */
    private $action;

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
        $this->paymentMethodStatus = $this->createMock(
            PaymentMethodStatus::class
        );
        $this->authorization = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->response = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->redirect = $this->getMockForAbstractClass(RedirectInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->action = $objectManagerHelper->getObject(
            HistoryIndexAction::class,
            [
                'moduleConfig' => $this->moduleConfig,
                'userContext' => $this->userContext,
                'paymentMethodStatus' => $this->paymentMethodStatus,
                'authorization' => $this->authorization,
                'logger' => $this->logger,
                '_response' => $this->response,
                '_actionFlag' => $this->actionFlag,
                '_redirect' => $this->redirect,
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
        $this->moduleConfig->expects($this->once())->method('isActive')->willReturn(true);
        $this->paymentMethodStatus->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->authorization->expects($this->once())->method('isAllowed')
            ->with(AbstractAction::COMPANY_CREDIT_RESOURCE)
            ->willReturn(true);
        $request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getFullActionName', 'getRouteName', 'isDispatched']
        );
        $this->assertEquals($this->response, $this->action->dispatch($request));
    }

    /**
     * Test for execute method with disabled payment.
     *
     * @return void
     */
    public function testExecuteWithDisabledPayment()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage('Page not found.');
        $this->moduleConfig->expects($this->once())->method('isActive')->willReturn(true);
        $this->paymentMethodStatus->expects($this->once())->method('isEnabled')->willReturn(false);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->action->dispatch($request);
    }

    /**
     * Test for execute method with non-authenticated user.
     *
     * @return void
     */
    public function testExecuteWithNonAuthenticatedUser()
    {
        $this->moduleConfig->expects($this->once())->method('isActive')->willReturn(true);
        $this->paymentMethodStatus->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(null);
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);
        $this->redirect->expects($this->once())
            ->method('redirect')->with($this->response, 'customer/account/login')->willReturn($this->response);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->assertEquals($this->response, $this->action->dispatch($request));
    }

    /**
     * Test for execute method without permissions.
     *
     * @return void
     */
    public function testExecuteWithoutPermissions()
    {
        $this->moduleConfig->expects($this->once())->method('isActive')->willReturn(true);
        $this->paymentMethodStatus->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->authorization->expects($this->once())->method('isAllowed')
            ->with(AbstractAction::COMPANY_CREDIT_RESOURCE)
            ->willReturn(false);
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);
        $this->redirect->expects($this->once())
            ->method('redirect')->with($this->response, 'noroute')->willReturn($this->response);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->assertEquals($this->response, $this->action->dispatch($request));
    }
}
