<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\Step\Structure\Category;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Step\Structure\Category\Tree;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Adminhtml\SharedCatalog\Wizard\Step\Structure\CategoryTree.
 */
class TreeTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Tree|MockObject
     */
    private $tree;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var UrlBuilder|MockObject
     */
    private $urlBuilder;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepositoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepositoryMock = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->tree = $this->objectManagerHelper->getObject(
            Tree::class,
            [
                'context' => $this->contextMock,
                'urlBuilder' => $this->urlBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock
            ]
        );
    }

    /**
     * Test for getAssignUrl().
     *
     * @return void
     */
    public function testGetAssignUrl()
    {
        $assignedUrl = 'test/url';
        $route = Tree::CATEGORY_ASSIGN_ROUTE;
        $this->urlBuilder->expects($this->exactly(1))->method('getUrl')->with($route)->willReturn($assignedUrl);

        $this->assertEquals($assignedUrl, $this->tree->getAssignUrl());
    }
}
