<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Block;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\QuickOrder\Block\Link;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $_objectManagerHelper;

    protected function setUp(): void
    {
        $this->_objectManagerHelper = new ObjectManager($this);
    }

    public function testGetHref()
    {
        $path = 'quickorder';
        $url = 'http://example.com/';

        $urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $urlBuilder->expects($this->once())->method('getUrl')->with($path)->willReturn($url . $path);

        $context = $this->_objectManagerHelper->getObject(
            Context::class,
            ['urlBuilder' => $urlBuilder]
        );
        $link = $this->_objectManagerHelper->getObject(Link::class, ['context' => $context]);
        $this->assertEquals($url . $path, $link->getHref());
    }

    public function testToHtml()
    {
        /** @var Data|MockObject $customerHelper */
        $customerHelper = $this->getMockBuilder(
            Data::class
        )->disableOriginalConstructor()
            ->getMock();

        /** @var Link $block */
        $block = $this->_objectManagerHelper->getObject(
            Link::class,
            ['customerHelper' => $customerHelper]
        );

        $customerHelper->expects($this->once())->method('isSkuApplied')->willReturn(false);

        $this->assertEquals('', $block->toHtml());
    }
}
