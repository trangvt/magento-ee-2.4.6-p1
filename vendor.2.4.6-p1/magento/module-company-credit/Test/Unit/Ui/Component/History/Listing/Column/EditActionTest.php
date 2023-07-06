<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History\Listing\Column;

use Magento\CompanyCredit\Ui\Component\History\Listing\Column\EditAction;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditActionTest extends TestCase
{
    /**
     * @var EditAction
     */
    private $editActionColumn;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $context = $this->createMock(
            ContextInterface::class
        );
        $processor = $this->createMock(
            Processor::class
        );
        $context->expects($this->never())->method('getProcessor')->willReturn($processor);
        $context->expects($this->once())->method('getFilterParam')->willReturn(1);

        $objectManager = new ObjectManager($this);
        $this->editActionColumn = $objectManager->getObject(
            EditAction::class,
            [
                'context' => $context,
                'urlBuilder' => $this->urlBuilder
            ]
        );
        $this->editActionColumn->setData('name', 'action');
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['entity_id' => 1, 'type' => 1, 'comment' => 'test1'],
                    ['entity_id' => 1, 'type' => 4, 'comment' => 'test2'],
                ]
            ]
        ];

        $creditModal = 'company_form.company_form.modalContainer.company_credit_form_modal';
        $amountField = $creditModal . '.reimburse_balance.amount';
        $expected = [
            'data' => [
                'items' => [
                    ['entity_id' => 1, 'type' => 1, 'comment' => 'test1'],
                    [
                        'entity_id' => 1,
                        'type' => 4,
                        'comment' => 'test2',
                        'credit_comment' => 'test2',
                        'action' => [
                            'edit' => [
                                'href' => 'credit/*/edit/id/1',
                                'label' => __('Edit'),
                                'hidden' => false,
                                'callback' => [
                                    [
                                        'provider' => $creditModal,
                                        'target' => 'openModal',
                                        'params' => [
                                            'url' => 'credit/*/edit/id/1',
                                            'item' => [
                                                'entity_id' => 1,
                                                'type' => 4,
                                                'comment' => 'test2',
                                                'credit_comment' => 'test2'
                                            ],
                                        ],
                                    ],
                                    [
                                        'provider' => $amountField,
                                        'target' => 'disable'
                                    ]
                                ]
                            ]
                        ],
                    ],
                ]
            ]
        ];

        $this->urlBuilder->expects($this->atLeastOnce())->method('getUrl')
            ->with('credit/*/edit', ['id' => 1, 'store' => 1])->willReturn('credit/*/edit/id/1');

        $this->assertEquals($expected, $this->editActionColumn->prepareDataSource($dataSource));
    }
}
