<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\Action;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Console\Request;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\Config;
use Magento\RequisitionList\Model\RequisitionList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequestValidator.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestValidatorTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListRepository = $this
            ->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPost', 'getParam'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->requestValidator = $objectManager->getObject(
            RequestValidator::class,
            [
                '_request' => $this->request,
                'moduleConfig' => $this->moduleConfig,
                'userContext' => $this->userContext,
                'formKeyValidator' => $this->formKeyValidator,
                'resultFactory' => $this->resultFactory,
                'urlBuilder' => $this->urlBuilder,
                'requisitionListRepository' => $this->requisitionListRepository
            ]
        );
    }

    /**
     * Test getResult.
     *
     * @return void
     */
    public function testGetResult()
    {
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result->expects($this->once())->method('setPath')
            ->with('customer/account/login')
            ->willReturnSelf();
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')->willReturn(null);
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);

        $this->assertEquals($result, $this->requestValidator->getResult($this->request));
    }

    /**
     * Test getResult with NULL result.
     *
     * @return void
     */
    public function testGetResultWithNullResult()
    {
        $userId = 2;
        $this->userContext->expects($this->once())->method('getUserType')->willReturn(
            UserContextInterface::USER_TYPE_CUSTOMER
        );
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(1);
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->atLeastOnce())->method('validate')->willReturn(true);
        $requisitionList = $this->getMockBuilder(RequisitionList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListRepository->expects($this->once())->method('get')->willReturn($requisitionList);
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($userId);
        $requisitionList->expects($this->once())->method('getCustomerId')->willReturn($userId);

        $this->assertNull($this->requestValidator->getResult($this->request));
    }

    /**
     * Test getResult with empty list ID.
     *
     * @return void
     */
    public function testGetResultWithEmptyListId()
    {
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(null);
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(false);

        $this->assertNull($this->requestValidator->getResult($this->request));
    }

    /**
     * Test getResult with referer url.
     *
     * @return void
     */
    public function testGetResultWithRefererUrl()
    {
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(false);
        $result->expects($this->once())->method('setRefererUrl')->willReturnSelf();

        $this->assertEquals($result, $this->requestValidator->getResult($this->request));
    }

    /**
     * Test getResult with exception.
     *
     * @return void
     */
    public function testGetResultWithException()
    {
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(1);
        $this->requisitionListRepository->expects($this->atLeastOnce())->method('get')->willThrowException(
            new NoSuchEntityException(__('Exception Message'))
        );

        $this->assertEquals($result, $this->requestValidator->getResult($this->request));
    }
}
