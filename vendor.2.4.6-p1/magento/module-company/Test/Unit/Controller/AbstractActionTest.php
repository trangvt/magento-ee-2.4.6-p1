<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller;

use Magento\Company\Controller\Index\Index;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Model\Url;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AbstractActionTest extends TestCase
{
    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

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
     * @var Url|MockObject
     */
    private $customerUrl;

    /**
     * @var Index
     */
    private $action;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyContext = $this->createMock(CompanyContext::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->response = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->customerUrl = $this->createMock(Url::class);
        $this->redirect = $this->getMockForAbstractClass(RedirectInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->action = $objectManagerHelper->getObject(
            Index::class,
            [
                'companyContext' => $this->companyContext,
                'logger' => $this->logger,
                '_response' => $this->response,
                '_actionFlag' => $this->actionFlag,
                '_redirect' => $this->redirect,
                'customerUrl' => $this->customerUrl
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
        $this->companyContext->expects($this->once())->method('isCustomerLoggedIn')->willReturn(true);
        $request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getFullActionName', 'getRouteName', 'isDispatched']
        );
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(true);
        $this->assertEquals($this->response, $this->action->dispatch($request));
    }

    /**
     * Test for execute method with disabled module.
     *
     * @return void
     */
    public function testExecuteWithDisabledModule()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage('Page not found.');
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(false);
        $this->action->dispatch($request);
    }

    /**
     * Test for execute method with non-authenticated user.
     *
     * @return void
     */
    public function testExecuteWithNonAuthenticatedUser()
    {
        $customerLoginUrl = 'http://{MAGENTO_DOMAIN}/customer/account/login';
        $this->customerUrl->expects($this->once())
            ->method('getLoginUrl')
            ->willReturn($customerLoginUrl);
        $this->companyContext->expects($this->once())->method('isCustomerLoggedIn')->willReturn(false);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(true);
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);
        $this->redirect->expects($this->once())->method('redirect')
            ->with($this->response, $customerLoginUrl, []);
        $this->assertEquals($this->response, $this->action->dispatch($request));
    }
}
