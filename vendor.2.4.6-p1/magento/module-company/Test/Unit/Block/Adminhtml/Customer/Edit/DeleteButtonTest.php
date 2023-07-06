<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Adminhtml\Customer\Edit;

use Magento\Company\Block\Adminhtml\Customer\Edit\DeleteButton;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Block\Adminhtml\Customer\Edit\DeleteButton class.
 */
class DeleteButtonTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DeleteButton|MockObject
     */
    private $deleteButton;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $accountManagementMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->accountManagementMock = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->deleteButton = $this->objectManagerHelper->getObject(
            DeleteButton::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'request' => $this->requestMock,
                'accountManagement' => $this->accountManagementMock
            ]
        );
    }

    /**
     * Test for method getButtonData.
     *
     * @param array $result
     * @return void
     * @dataProvider dataProviderGetButtonData
     */
    public function testGetButtonData(array $result)
    {
        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturn(1);
        $this->urlBuilderMock->expects($this->atLeastOnce())
            ->method('getUrl')
            ->willReturn('*/*/test');
        $this->accountManagementMock->expects($this->once())
            ->method('isReadonly')
            ->willReturn(false);
        $this->assertEquals($result, $this->deleteButton->getButtonData());
    }

    /**
     * Test for method getButtonData with readonly.
     *
     * @return void
     */
    public function testGetButtonDataWithReadonly()
    {
        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturn(1);
        $this->urlBuilderMock->expects($this->never())
            ->method('getUrl');
        $this->accountManagementMock->expects($this->once())
            ->method('isReadonly')
            ->willReturn(true);
        $this->assertEquals([], $this->deleteButton->getButtonData());
    }

    /**
     * Data provider for getButtonData.
     *
     * @return array
     */
    public function dataProviderGetButtonData()
    {
        return [
            [
                [
                    'label' => new Phrase('Delete Customer'),
                    'class' => 'delete',
                    'id' => 'customer-delete-button',
                    'data_attribute' => [
                        'mage-init' => '{"Magento_Company/js/actions/delete-customer":'
                            . '{"url": "*/*/test",
                                        "validate": "*/*/test"}}',
                    ],
                    'on_click' => '',
                    'sort_order' => 20,
                    'aclResource' => 'Magento_Customer::delete',
                ]
            ]
        ];
    }
}
