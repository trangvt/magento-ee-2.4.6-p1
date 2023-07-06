<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\History\Source;

use Magento\CompanyCredit\Model\History\Source\Operation;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class OperationTest extends TestCase
{
    /**
     * @var Operation
     */
    private $operation;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->operation = $objectManager->getObject(
            Operation::class
        );
    }

    /**
     * Test for method getAllOptions.
     *
     * @return void
     */
    public function testGetAllOptions()
    {
        $expectedResult = array_map(
            function ($label, $value) {
                return ['value' => $value, 'label' => $label];
            },
            Operation::getOptionArray(),
            array_keys(Operation::getOptionArray())
        );
        $this->assertEquals($expectedResult, $this->operation->getAllOptions());
    }
}
