<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Profile;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Profile\EditPost;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Controller\Profile\EditPost class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditPostTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var CompanyProfile|MockObject
     */
    private $companyProfile;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var EditPost
     */
    private $editPost;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyProfile = $this->getMockBuilder(CompanyProfile::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPost', 'getParams'])
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this
            ->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->editPost = $objectManager->getObject(
            EditPost::class,
            [
                'companyManagement' => $this->companyManagement,
                'formKeyValidator' => $this->formKeyValidator,
                'companyProfile' => $this->companyProfile,
                'companyRepository' => $this->companyRepository,
                '_request' => $this->request,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'companyContext' => $this->companyContext,
                'messageManager' => $this->messageManager,
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
        $customerId = 1;
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $resultRedirect->expects($this->once())->method('setPath')->with('*/profile/')->willReturnSelf();
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->once())->method('validate')->with($this->request)->willReturn(true);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn(1);
        $this->request->expects($this->once())->method('getParams')->willReturn([]);
        $this->companyProfile->expects($this->once())->method('populate')->with($company, []);
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturnSelf();
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('The changes you made on the company profile have been successfully saved.'))
            ->willReturnSelf();

        $this->assertSame($resultRedirect, $this->editPost->execute());
    }

    /**
     * Test execute with invalid form key.
     *
     * @return void
     */
    public function testExecuteWithInvalidFormKey()
    {
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $resultRedirect->expects($this->once())->method('setPath')->with('*/profile/')->willReturnSelf();
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->once())->method('validate')->with($this->request)->willReturn(false);

        $this->assertSame($resultRedirect, $this->editPost->execute());
    }

    /**
     * Test execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $customerId = 1;
        $exception = new \Exception();
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $resultRedirect->expects($this->atLeastOnce())
            ->method('setPath')
            ->withConsecutive(['*/profile/'], ['*/profile/edit'])
            ->willReturnSelf();
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->once())->method('validate')->with($this->request)->willReturn(true);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn(1);
        $this->request->expects($this->once())->method('getParams')->willReturn([]);
        $this->companyProfile->expects($this->once())->method('populate')->with($company, []);
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($company)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('An error occurred on the server. Your changes have not been saved.'))
            ->willReturnSelf();

        $this->assertSame($resultRedirect, $this->editPost->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $customerId = 1;
        $phrase = new Phrase('exception');
        $localizedException = new LocalizedException($phrase);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $resultRedirect->expects($this->atLeastOnce())
            ->method('setPath')
            ->withConsecutive(['*/profile/'], ['*/profile/edit'])
            ->willReturnSelf();
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->once())->method('validate')->with($this->request)->willReturn(true);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn(1);
        $this->request->expects($this->once())->method('getParams')->willReturn([]);
        $this->companyProfile->expects($this->once())->method('populate')->with($company, []);
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($company)
            ->willThrowException($localizedException);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('You must fill in all required fields before you can continue.'))
            ->willReturnSelf();

        $this->assertSame($resultRedirect, $this->editPost->execute());
    }
}
