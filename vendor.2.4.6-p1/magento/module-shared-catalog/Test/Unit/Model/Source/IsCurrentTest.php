<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Source\IsCurrent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IsCurrent source model.
 */
class IsCurrentTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var IsCurrent
     */
    private $isCurrent;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->isCurrent = $this->objectManagerHelper->getObject(
            IsCurrent::class,
            []
        );
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        $result = [
            [
                'label' => __('Yes'),
                'value' => 1
            ],
            [
                'label' => __('No'),
                'value' => 0
            ]
        ];

        $this->assertEquals($result, $this->isCurrent->toOptionArray());
    }
}
