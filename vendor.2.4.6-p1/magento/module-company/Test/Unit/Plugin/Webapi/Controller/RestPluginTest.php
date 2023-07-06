<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Webapi\Controller;

use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Company\Plugin\Webapi\Controller\CustomerResolver;
use Magento\Company\Plugin\Webapi\Controller\RestPlugin;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\Account\Logout;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RestPluginTest extends TestCase
{
    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var Logout|MockObject
     */
    private $logoutAction;

    /**
     * @var ErrorProcessor|MockObject
     */
    private $errorProcessor;

    /**
     * @var CustomerResolver|MockObject
     */
    private $customerResolver;

    /**
     * @var Response|MockObject
     */
    private $response;

    /**
     * @var RestPlugin
     */
    private $plugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permission = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoginAllowed'])
            ->getMockForAbstractClass();
        $this->logoutAction = $this->createMock(
            Logout::class
        );
        $this->errorProcessor = $this->createPartialMock(
            ErrorProcessor::class,
            ['maskException']
        );
        $this->customerResolver = $this->createPartialMock(
            CustomerResolver::class,
            ['getCustomer']
        );
        $this->response = $this->createPartialMock(
            Response::class,
            ['setException']
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            RestPlugin::class,
            [
                'permission' => $this->permission,
                'logoutAction' => $this->logoutAction,
                'errorProcessor' => $this->errorProcessor,
                'customerResolver' => $this->customerResolver,
                'response' => $this->response,
            ]
        );
    }

    /**
     * Test aroundDispatch method.
     *
     * @return void
     */
    public function testAroundDispatch()
    {
        $subject = $this->createMock(
            Rest::class
        );
        $request = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPost'])
            ->getMockForAbstractClass();
        $customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $redirect = $this->createMock(
            Redirect::class
        );
        $phrase = new Phrase('The consumer isn\'t authorized to access resource.');
        $exception = new AuthorizationException($phrase);
        $webapiException = $this->createMock(
            Exception::class
        );
        $proceed = function ($request) {
            return true;
        };
        $request->expects($this->once())->method('isPost')->willReturn(true);
        $this->customerResolver->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->permission->expects($this->once())->method('isLoginAllowed')->with($customer)->willReturn(false);
        $this->logoutAction->expects($this->once())->method('execute')->willReturn($redirect);
        $this->errorProcessor->expects($this->once())
            ->method('maskException')
            ->with($exception)
            ->willReturn($webapiException);
        $this->response->expects($this->once())->method('setException')->with($webapiException)->willReturnSelf();

        $this->assertEquals($this->response, $this->plugin->aroundDispatch($subject, $proceed, $request));
    }

    /**
     * Test aroundDispatch method with guest customer.
     *
     * @return void
     */
    public function testAroundDispatchWithGuestCustomer()
    {
        $request = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPost'])
            ->getMockForAbstractClass();
        $subject = $this->createMock(
            Rest::class
        );
        $request->expects($this->once())->method('isPost')->willReturn(true);
        $this->customerResolver->expects($this->once())->method('getCustomer')->willReturn(null);
        $proceed = function ($request) {
            return true;
        };
        $this->assertTrue($this->plugin->aroundDispatch($subject, $proceed, $request));
    }
}
