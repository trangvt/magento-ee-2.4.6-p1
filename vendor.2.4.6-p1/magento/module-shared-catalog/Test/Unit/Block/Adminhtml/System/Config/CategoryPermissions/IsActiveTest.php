<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\System\Config\CategoryPermissions;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Block\Adminhtml\System\Config\CategoryPermissions\IsActive;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for block Magento\SharedCatalog\Test\Unit\Block\Adminhtml\System\Config\CategoryPermissions\IsActive.
 */
class IsActiveTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var IsActive
     */
    private $isActive;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->isActive = $objectManager->getObject(
            IsActive::class,
            [
                '_scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * Test for render method.
     *
     * @return void
     */
    public function testRender()
    {
        $htmlId = 'htmlId';
        $elementHtml = 'Element Html';
        $element = $this->getMockBuilder(AbstractElement::class)
            ->setMethods(['getHtmlId', 'setDisabled', 'getElementHtml'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig->expects($this->atLeastOnce())
            ->method('isSetFlag')->with('btob/website_configuration/sharedcatalog_active')->willReturn(true);
        $element->expects($this->atLeastOnce())->method('getHtmlId')->willReturn($htmlId);
        $element->expects($this->once())->method('setDisabled')->with(true)->willReturnSelf();
        $element->expects($this->once())->method('getElementHtml')->willReturn($elementHtml);
        $this->assertEquals(
            sprintf(
                '<tr id="row_%1$s">'
                . '<td class="label"><label for="%1$s"><span></span></label></td>'
                . '<td class="value">%2$s</td><td class=""></td>'
                . '</tr>',
                $htmlId,
                $elementHtml
            ),
            $this->isActive->render($element)
        );
    }
}
