<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\RequisitionList\Model\RequisitionListItem\Validation;
use Magento\RequisitionList\Model\RequisitionListItem\Validator\Sku;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Sku|MockObject
     */
    private $skuValidatorMock;

    /**
     * @var Validation|MockObject
     */
    private $validation;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->skuValidatorMock = $this->getMockBuilder(
            Sku::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->validation = $this->objectManagerHelper->getObject(
            Validation::class,
            [
                'validators' => [
                    $this->skuValidatorMock
                ]
            ]
        );
    }

    /**
     * Test isValid
     *
     * @param array $errors
     * @param bool $isValid
     * @return void
     *
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(array $errors, $isValid)
    {
        $itemMock = $this->getMockBuilder(RequisitionListItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->skuValidatorMock->expects($this->any())
            ->method('validate')
            ->willReturn($errors);

        $this->assertEquals(
            $isValid,
            $this->validation->isValid($itemMock)
        );
    }

    /**
     * Data provider for isValid
     *
     * @return array
     */
    public function isValidDataProvider()
    {
        return [
            [
                ['error'],
                false
            ],
            [
                [],
                true
            ]
        ];
    }
}
