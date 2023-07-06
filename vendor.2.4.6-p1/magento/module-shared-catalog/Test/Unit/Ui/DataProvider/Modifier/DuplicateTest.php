<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Ui\DataProvider\Modifier\Duplicate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for duplicate modifier.
 */
class DuplicateTest extends TestCase
{
    /**
     * @var Duplicate
     */
    private $modifier;

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
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            Duplicate::class,
            [
                'request' => $this->request,
                'urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test modifyData method with create action.
     *
     * @return void
     */
    public function testModifyDataCreate()
    {
        $data = [
            'items' => [
                [
                    SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => 1,
                    'catalog_details' => ['name' => 'Name']
                ]
            ],
            'config' => ['submit_url' => 'shared_catalog/sharedCatalog/save']
        ];
        $this->request->expects($this->once())->method('getActionName')->willReturn('create');
        $this->assertEquals($data, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyData method with duplicate action.
     *
     * @return void
     */
    public function testModifyDataDuplicate()
    {
        $data = [
            'items' => [
                [
                    SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => 1,
                    'catalog_details' => ['name' => 'Name']
                ]
            ],
            'config' => ['submit_url' => 'shared_catalog/sharedCatalog/save']
        ];
        $expect = [
            'items' => [
                [
                    'duplicate_id' => 1,
                    'catalog_details' => [
                        'name' => 'Duplicate of Name',
                        'type' => 0,
                        'created_at' => null,
                        'customer_group_id' => null
                    ]
                ]
            ],
            'config' => ['submit_url' => 'shared_catalog/sharedCatalog/duplicatePost']
        ];
        $this->request->expects($this->once())->method('getActionName')->willReturn('duplicate');
        $this->urlBuilder->expects($this->once())->method('getUrl')->willReturnArgument(0);
        $this->assertEquals($expect, $this->modifier->modifyData($data));
    }
}
