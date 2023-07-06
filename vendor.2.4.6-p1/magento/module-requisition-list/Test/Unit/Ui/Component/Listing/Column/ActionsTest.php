<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\RequisitionList\Ui\Component\Listing\Column\Actions;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Magento\RequisitionList\Ui\Component\Listing\Column\Actions class.
 */
class ActionsTest extends TestCase
{
    const TEST_NAME = 'test_name';

    /**
     * @var Actions
     */
    private $actions;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $urlBuilder->expects($this->atLeastOnce())->method('getUrl')->willReturn('');
        $context = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->actions = $objectManager->getObject(
            Actions::class,
            [
                'context' => $context,
                'urlBuilder' => $urlBuilder,
            ]
        );
    }

    /**
     * Units tests for 'prepareDataSource' method.
     *
     * @param string $name
     * @param array $inputData
     * @param array $expectedData
     * @return void
     *
     * @dataProvider prepareDataSourceDataProvider
     */
    public function testPrepareDataSource($name, array $inputData, array $expectedData)
    {
        $this->actions->setData('name', $name);
        $this->assertEquals($expectedData, $this->actions->prepareDataSource($inputData));
    }

    /**
     * Data provider for 'prepareDataSource' method.
     *
     * @return array
     */
    public function prepareDataSourceDataProvider()
    {
        return [
            [
                self::TEST_NAME,
                $this->buildDataSource([
                    [
                        'entity_id' => 1
                    ],
                    [
                        'entity_id' => 2
                    ]
                ]),
                $this->buildDataSource([
                    [
                        'entity_id' => 1,
                        self::TEST_NAME => [
                            'view' => [
                                'href' => '',
                                'label' => __('View'),
                                'hidden' => false,
                            ]
                        ],
                    ],
                    [
                        'entity_id' => 2,
                        self::TEST_NAME => [
                            'view' => [
                                'href' => '',
                                'label' => __('View'),
                                'hidden' => false,
                            ]
                        ],
                    ]
                ])
            ]
        ];
    }

    /**
     * Build data source.
     *
     * @param array $items
     * @return array
     */
    private function buildDataSource(array $items)
    {
        return [
            'data' => [
                'items' => $items
            ]
        ];
    }
}
