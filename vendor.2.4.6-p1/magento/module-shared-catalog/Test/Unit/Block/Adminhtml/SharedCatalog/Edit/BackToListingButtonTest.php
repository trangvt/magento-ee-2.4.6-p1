<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Edit\BackToListingButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackToListingButtonTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var BackToListingButton|MockObject
     */
    protected $backToListingButtonMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->getMockForAbstractClass(
            UrlInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getUrl']
        );
        $this->context = $this->createPartialMock(Context::class, ['getUrlBuilder']);
        $this->objectManager = new ObjectManager($this);
    }

    public function testGetButtonData()
    {
        $route = '*/*/';
        $backUrl = 'test url';
        $expectedResult = [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $backUrl),
            'class' => 'back',
            'sort_order' => 10
        ];
        $this->context->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with($route)
            ->willReturn($backUrl);
        $this->backToListingButtonMock = $this->objectManager->getObject(
            BackToListingButton::class,
            [
                'context' => $this->context,
            ]
        );
        $actualResult = $this->backToListingButtonMock->getButtonData();
        $this->assertEquals($expectedResult, $actualResult);
    }
}
