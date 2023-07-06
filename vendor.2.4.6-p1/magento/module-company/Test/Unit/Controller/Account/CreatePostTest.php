<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Account;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Controller\Account\CreatePost;
use Magento\Company\Model\Action\Validator\Captcha;
use Magento\Company\Model\Create\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for Create Post controller.
 * @see \Magento\Company\Controller\Account\CreatePost
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePostTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $objectHelper;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var Captcha|MockObject
     */
    private $captchaValidator;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $customerAccountManagement;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    private $customerDataFactory;

    /**
     * @var Session|MockObject
     */
    private $companyCreateSession;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CreatePost
     */
    private $createPost;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->objectHelper = $this->createMock(DataObjectHelper::class);
        $this->formKeyValidator = $this->createMock(Validator::class);
        $this->captchaValidator = $this->createMock(Captcha::class);
        $this->customerAccountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['createAccount'])
            ->getMockForAbstractClass();
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->customerAccountManagement->expects($this->any())->method('createAccount')->willReturn($customer);
        $this->customerDataFactory = $this->createPartialMock(
            CustomerInterfaceFactory::class,
            ['create']
        );
        $this->customerDataFactory->expects($this->any())->method('create')->willReturn($customer);
        $this->companyCreateSession = $this->createMock(Session::class);
        $this->request = $this->createMock(Http::class);
        $this->request->expects($this->any())->method('getPost')->willReturn([]);
        $this->resultRedirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );
        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->redirect);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $objectManager = new ObjectManager($this);
        $this->createPost = $objectManager->getObject(
            CreatePost::class,
            [
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'userContext' => $this->userContext,
                'logger' => $this->logger,
                'objectHelper' => $this->objectHelper,
                'formKeyValidator' => $this->formKeyValidator,
                'captchaValidator' => $this->captchaValidator,
                'customerAccountManagement' => $this->customerAccountManagement,
                'customerDataFactory' => $this->customerDataFactory,
                'companyCreateSession' => $this->companyCreateSession
            ]
        );
    }

    /**
     * Test execute
     *
     * @param bool $isPost
     * @param bool $isFormValid
     * @param bool $isCaptchaValid
     * @dataProvider dataProviderExecute
     */
    public function testExecute($isPost, $isFormValid, $isCaptchaValid)
    {
        $this->prepareReturnValues($isPost, $isFormValid, $isCaptchaValid);

        $this->assertInstanceOf(Redirect::class, $this->createPost->execute());
    }

    /**
     * Test execute with exception
     */
    public function testExecuteWithException()
    {
        $this->prepareReturnValues();
        $exception = new \Exception();
        $this->customerAccountManagement->expects($this->any())->method('createAccount')
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->logger->expects($this->once())->method('critical');

        $this->assertInstanceOf(Redirect::class, $this->createPost->execute());
    }

    /**
     * Test execute with LocalizedException
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareReturnValues();
        $phrase = new Phrase(__('Exception'));
        $exception = new LocalizedException($phrase);
        $this->customerAccountManagement->expects($this->any())->method('createAccount')
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertInstanceOf(Redirect::class, $this->createPost->execute());
    }

    /**
     * DataProvider execute
     *
     * @return array
     */
    public function dataProviderExecute()
    {
        return [
            [false, false, false],
            [true, false, false],
            [false, true, false],
            [false, false, true],
            [true, true, false],
            [false, true, true],
            [true, true, true]
        ];
    }

    /**
     * Prepare return values
     *
     * @param bool $isPost
     * @param bool $isFormValid
     * @param bool $isCaptchaValid
     */
    private function prepareReturnValues($isPost = true, $isFormValid = true, $isCaptchaValid = true)
    {
        $this->request->expects($this->any())->method('isPost')->willReturn($isPost);
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn($isFormValid);
        $this->captchaValidator->expects($this->any())->method('validate')->willReturn($isCaptchaValid);
    }
}
