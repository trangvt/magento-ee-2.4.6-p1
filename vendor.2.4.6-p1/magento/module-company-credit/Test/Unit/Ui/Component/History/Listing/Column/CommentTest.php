<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History\Listing\Column;

use Magento\CompanyCredit\Model\Sales\OrderLocator;
use Magento\CompanyCredit\Ui\Component\History\Listing\Column\Comment;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CompanyCredit\Ui\Component\History\Listing\Column\Comment class.
 */
class CommentTest extends TestCase
{
    /**
     * @var Comment
     */
    private $commentColumn;

    /**
     * @var OrderLocator|MockObject
     */
    private $orderLocator;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderLocator = $this->getMockBuilder(OrderLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $escaper->expects($this->any())
            ->method('escapeHtml')
            ->willReturnArgument(0);
        $context = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);

        $serializer = $this->createMock(Json::class);
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->commentColumn = $objectManager->getObject(
            Comment::class,
            [
                'context' => $context,
                'orderLocator' => $this->orderLocator,
                'urlBuilder' => $this->urlBuilder,
                'escaper' => $escaper,
                'serializer' => $serializer
            ]
        );
        $this->commentColumn->setData('name', 'comment');
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSourceWithCustomComment()
    {
        $dataSource = ['data' => ['items' => [['type' => 1, 'comment' => json_encode(['custom' => 'test'])]]]];
        $expected = ['data' => ['items' => [['type' => 1, 'comment' => 'test']]]];

        $this->assertEquals($expected, $this->commentColumn->prepareDataSource($dataSource));
    }

    /**
     * Test method for prepareDataSource.
     *
     * @param array $orderCreditMemoData
     * @return void
     * @dataProvider orderCreditMemoDataProvider
     */
    public function testPrepareDataSourceWithOrderCreditMemo(array $orderCreditMemoData)
    {
        $order = $this->createMock(Order::class);
        $this->orderLocator->expects($this->once())
            ->method('getOrderByIncrementId')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn(
                $orderCreditMemoData['actualData']['data']['incrementId']
            );
        $order->expects($this->once())
            ->method('getEntityId')
            ->willReturn(
                $orderCreditMemoData['actualData']['data']['entityId']
            );
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->with(
                'sales/order/view',
                [
                    'order_id' => $orderCreditMemoData['actualData']['data']['entityId']
                ]
            )->willReturn(
                'sales/order/view/order_id/' .
                $orderCreditMemoData['actualData']['data']['entityId']
            );

        $this->assertEquals(
            $orderCreditMemoData['expectedData'],
            $this->commentColumn->prepareDataSource(
                $orderCreditMemoData['actualData']
            )
        );
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSourceWithOrderWithException()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['type' => 1, 'comment' => json_encode(['system' => ['order' => '1']])]
                ]
            ]
        ];
        $expected = [
            'data' => ['items' => [['type' => 1, 'comment' => '']]]
        ];

        $this->orderLocator->expects($this->once())
            ->method('getOrderByIncrementId')
            ->willThrowException(new NoSuchEntityException());

        $this->assertEquals($expected, $this->commentColumn->prepareDataSource($dataSource));
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSourceWithExceedLimitNo()
    {
        $comment = json_encode(
            [
                'system' => [
                    'exceed_limit' => [
                        'value' => false,
                        'company_name' => 'test',
                        'user_name' => 'user'
                    ]
                ]
            ]
        );

        $dataSource = ['data' => ['items' => [['type' => 1, 'comment' => $comment]]]];
        $expected = [
            'data' => [
                'items' => [
                    ['type' => 1, 'comment' => 'user made an update. test cannot exceed the Credit Limit.']
                ]
            ]
        ];

        $this->assertEquals($expected, $this->commentColumn->prepareDataSource($dataSource));
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSourceWithExceedLimitYes()
    {
        $comment = json_encode(
            [
                'system' => [
                    'exceed_limit' => [
                        'value' => true,
                        'company_name' => 'test',
                        'user_name' => 'user'
                    ]
                ]
            ]
        );

        $dataSource = ['data' => ['items' => [['type' => 1, 'comment' => $comment]]]];
        $expected = [
            'data' => [
                'items' => [
                    ['type' => 1, 'comment' => 'user made an update. test can exceed the Credit Limit.']
                ]
            ]
        ];

        $this->assertEquals($expected, $this->commentColumn->prepareDataSource($dataSource));
    }

    /**
     * @return array
     */
    public function orderCreditMemoDataProvider(): array
    {
        return [
            [
                'order' => [
                    'actualData' => [
                        'data' => [
                            'incrementId' => 1111,
                            'entityId' => 1,
                            'items' => [
                                [
                                    'type' => 1,
                                    'comment' => json_encode(
                                        ['system' => ['order' => 1111]]
                                    )
                                ]
                            ]
                        ]
                    ],
                    'expectedData' => [
                        'data' => [
                            'incrementId' => 1111,
                            'entityId' => 1,
                            'items' => [
                                [
                                    'type' => 1,
                                    'comment' => 'Order # <a href="sales/order/view/order_id/1"">1111</a>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'creditMemo' => [
                    'actualData' => [
                        'data' => [
                            'incrementId' => 1111,
                            'entityId' => 1,
                            'items' => [
                                [
                                    'type' => 5,
                                    'comment' => json_encode(
                                        ['system' => ['order' => 1111]]
                                    )
                                ]
                            ]
                        ]
                    ],
                    'expectedData' => [
                        'data' => [
                            'incrementId' => 1111,
                            'entityId' => 1,
                            'items' => [
                                [
                                    'type' => 5,
                                    'comment' => 'Order # <a href="sales/order/view/order_id/1"">1111</a>'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
