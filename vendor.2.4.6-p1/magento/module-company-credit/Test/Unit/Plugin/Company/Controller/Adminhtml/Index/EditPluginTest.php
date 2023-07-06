<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Company\Controller\Adminhtml\Index;

use Magento\Company\Controller\Adminhtml\Index\Edit;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterfaceFactory;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\CompanyCredit\Plugin\Company\Controller\Adminhtml\Index\EditPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CompanyCredit\Plugin\Company\Controller\Adminhtml\Index\EditPlugin class.
 */
class EditPluginTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CreditLimitInterfaceFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var EditPlugin
     */
    private $editPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement = $this->getMockBuilder(
            CreditLimitManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteCurrency = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitFactory = $this
            ->getMockBuilder(CreditLimitInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->editPlugin = $objectManager->getObject(
            EditPlugin::class,
            [
                'request' => $this->request,
                'creditLimitManagement' => $this->creditLimitManagement,
                'websiteCurrency' => $this->websiteCurrency,
                'messageManager' => $this->messageManager,
                'creditLimitFactory' => $this->creditLimitFactory,
            ]
        );
    }

    /**
     * Test beforeExecute method.
     *
     * @return void
     */
    public function testBeforeExecute()
    {
        $companyId = 1;
        $creditCurrencyCode = 'USD';
        $this->request->expects(static::once())->method('getParam')->with('id')->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit->expects(static::once())->method('getCurrencyCode')->willReturn($creditCurrencyCode);
        $this->creditLimitManagement->expects(static::once())->method('getCreditByCompanyId')
            ->with($companyId)
            ->willReturn($creditLimit);
        $this->websiteCurrency->expects(static::once())
            ->method('isCreditCurrencyEnabled')
            ->with($creditCurrencyCode)
            ->willReturn(false);
        $this->messageManager->expects(self::atLeastOnce())->method('addNoticeMessage');
        $subject = $this->getMockBuilder(Edit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(
            $this->editPlugin->beforeExecute($subject),
            []
        );
    }

    /**
     * Test beforeExecute method with exception.
     *
     * @return void
     */
    public function testBeforeExecuteWithException()
    {
        $companyId = 1;
        $creditCurrencyCode = 'USD';
        $exception = new NoSuchEntityException();
        $this->request->expects(static::once())->method('getParam')->with('id')->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willThrowException($exception);
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $creditLimit->expects(static::once())->method('getCurrencyCode')->willReturn($creditCurrencyCode);
        $this->websiteCurrency->expects(static::once())
            ->method('isCreditCurrencyEnabled')
            ->with($creditCurrencyCode)
            ->willReturn(false);
        $this->messageManager->expects(self::atLeastOnce())->method('addNoticeMessage');
        $subject = $this->getMockBuilder(Edit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(
            $this->editPlugin->beforeExecute($subject),
            []
        );
    }
}
