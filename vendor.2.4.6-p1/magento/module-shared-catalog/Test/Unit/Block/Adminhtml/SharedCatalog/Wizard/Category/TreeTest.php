<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\Category;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Category\Tree;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for block Adminhtml\SharedCatalog\Wizard\Category\Tree.
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
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->tree = $this->objectManagerHelper->getObject(
            Tree::class,
            [
                'context' => $this->contextMock,
                'urlBuilder' => $this->urlBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for getTreeUrl().
     *
     * @return void
     */
    public function testGetTreeUrl()
    {
        $sharedCatalogId = 346;

        $param = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $this->request->expects($this->exactly(1))->method('getParam')->with($param)->willReturn($sharedCatalogId);

        $this->sharedCatalog->expects($this->exactly(1))->method('getId')->willReturn($sharedCatalogId);

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->with($sharedCatalogId)
            ->willReturn($this->sharedCatalog);

        $initUrl = 'test/url';
        $routeParams = [
            '_query' => [
                'filters' => [
                    'shared_catalog' => [
                        'id' => $sharedCatalogId
                    ]
                ]
            ]
        ];
        $routePath = Tree::TREE_INIT_ROUTE;
        $this->urlBuilder->expects($this->exactly(1))->method('getUrl')->with($routePath, $routeParams)
            ->willReturn($initUrl);

        $this->assertEquals($initUrl, $this->tree->getTreeUrl());
    }
}
