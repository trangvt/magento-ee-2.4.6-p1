<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Edit\DuplicateSharedCatalogButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Adminhtml\SharedCatalog\Edit\DuplicateSharedCatalogButtonTest.
 */
class DuplicateSharedCatalogButtonTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DuplicateSharedCatalogButton|MockObject
     */
    private $duplicateSharedCatalogButton;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam', 'getActionName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action = 'edit';
        $this->request->expects($this->exactly(1))->method('getActionName')->willReturn($action);

        $this->context = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->exactly(1))->method('getRequest')->willReturn($this->request);

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->duplicateSharedCatalogButton = $this->objectManagerHelper->getObject(
            DuplicateSharedCatalogButton::class,
            [
                'context' => $this->context,
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
        $duplicateUrl = 'url/duplicate';
        $expected = [
            'label' => __('Duplicate'),
            'class' => 'duplicate',
            'data_attribute' => [
                'mage-init' => [
                    'redirectionUrl' => ['url' => $duplicateUrl],
                ]
            ],
            'sort_order' => 50,
        ];

        $this->prepareGetDuplicateUrlMethod($duplicateUrl);

        $result = $this->duplicateSharedCatalogButton->getButtonData();
        $this->assertEquals($expected, $result);
    }

    /**
     * Prepare getDuplicateUrl().
     *
     * @param $duplicateUrl
     */
    private function prepareGetDuplicateUrlMethod($duplicateUrl)
    {
        $sharedCatalogParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $sharedCatalogId = 4676;
        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogParam)
            ->willReturn($sharedCatalogId);

        $route = '*/*/duplicate';
        $routeParams = [$sharedCatalogParam => $sharedCatalogId];
        $this->urlBuilder->expects($this->exactly(1))->method('getUrl')->with($route, $routeParams)
            ->willReturn($duplicateUrl);
    }
}
