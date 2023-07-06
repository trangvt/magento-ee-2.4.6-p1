<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\State\Category;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\State\Category\Tree;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Adminhtml\SharedCatalog\Wizard\State\Category\Tree.
 */
class TreeTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var UrlBuilder|MockObject
     */
    private $urlBuilder;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var Tree
     */
    private $tree;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlBuilder = $this->getMockBuilder(UrlBuilder::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getStoreId', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->tree = $this->objectManager->getObject(
            Tree::class,
            [
                'context' => $this->context,
                'urlBuilder' => $this->urlBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'data' => [],
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
        $routePath = Tree::TREE_INIT_ROUTE;
        $storeId = 'test store id';
        $url = 'test/url';
        $sharedCatalogUrlParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $sharedCatalogId = 234;
        $routeParams = [
            '_query' => [
                'filters' => [
                    'store' => [
                        'id' => $storeId
                    ],
                    'shared_catalog' => [
                        'id' => $sharedCatalogId
                    ]
                ]
            ]
        ];
        $catalogId = '234';

        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogUrlParam)
            ->willReturn($catalogId);

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->with($catalogId)
            ->willReturn($this->sharedCatalog);

        $this->sharedCatalog->expects($this->exactly(1))->method('getStoreId')->willReturn($storeId);

        $this->urlBuilder->expects($this->exactly(1))->method('getUrl')->with($routePath, $routeParams)
            ->willReturn($url);

        $this->assertEquals($url, $this->tree->getTreeUrl());
    }
}
