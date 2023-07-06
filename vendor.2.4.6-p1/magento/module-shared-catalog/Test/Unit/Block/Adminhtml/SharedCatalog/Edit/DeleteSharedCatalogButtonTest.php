<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Edit\DeleteSharedCatalogButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Adminhtml\SharedCatalog\Edit\DeleteSharedCatalogButton.
 */
class DeleteSharedCatalogButtonTest extends TestCase
{
    /**
     * @var DeleteSharedCatalogButton
     */
    private $deleteSharedCatalogButton;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->deleteSharedCatalogButton = $this->objectManager->getObject(
            DeleteSharedCatalogButton::class,
            [
                '_request' => $this->request,
                'urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test for getButtonData().
     *
     * @return void
     */
    public function testGetButtonData()
    {
        $deleteUrl = 'url/delete';
        $onClickFunction = 'deleteConfirm(\'' .
            __('This action cannot be undone. Are you sure you want to delete this catalog?') .
            '\', \'' . $deleteUrl . '\', {data: {}})';
        $expected = [
            'label' => __('Delete'),
            'class' => 'delete',
            'id' => 'shared-catalog-edit-delete-button',
            'on_click' => $onClickFunction,
            'sort_order' => 50,
        ];
        $this->request->expects($this->once())->method('getActionName')->willReturn('edit');
        $this->prepareGetDeleteUrlMethod($deleteUrl);

        $this->assertEquals($expected, $this->deleteSharedCatalogButton->getButtonData());
    }

    /**
     * Prepare getDeleteUrl().
     *
     * @param string $deleteUrl
     * @return void
     */
    private function prepareGetDeleteUrlMethod($deleteUrl)
    {
        $sharedCatalogParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $sharedCatalogId = 4676;
        $this->request->expects($this->once())->method('getParam')->with($sharedCatalogParam)
            ->willReturn($sharedCatalogId);

        $route = '*/*/delete';
        $routeParams = [$sharedCatalogParam => $sharedCatalogId];
        $this->urlBuilder->expects($this->once())->method('getUrl')->with($route, $routeParams)
            ->willReturn($deleteUrl);
    }

    /**
     * Test for getDeleteUrl().
     *
     * @return void
     */
    public function testGetDeleteUrl()
    {
        $duplicateUrl = 'url/delete';
        $this->prepareGetDeleteUrlMethod($duplicateUrl);

        $this->assertEquals($duplicateUrl, $this->deleteSharedCatalogButton->getDeleteUrl());
    }
}
