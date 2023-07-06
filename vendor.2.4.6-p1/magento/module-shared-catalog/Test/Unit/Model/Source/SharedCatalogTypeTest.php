<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\Source\SharedCatalogType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedCatalogTypeTest extends TestCase
{
    /**
     * @var SharedCatalog|MockObject
     */
    protected $sharedCatalog;

    /**
     * @var SharedCatalogType|MockObject
     */
    protected $sharedCatalogTypeMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->sharedCatalog = $this->createMock(SharedCatalog::class);
        $objectManager = new ObjectManager($this);
        $this->sharedCatalogTypeMock = $objectManager->getObject(
            SharedCatalogType::class,
            [
                'sharedCatalog' => $this->sharedCatalog,
            ]
        );
    }

    /**
     * Test for method toOptionArray
     */
    public function testToOptionArray()
    {
        $result = [
            [
                'label' => __('Public'),
                'value' => SharedCatalogInterface::TYPE_PUBLIC,
            ],
            [
                'label' => __('Custom'),
                'value' => SharedCatalogInterface::TYPE_CUSTOM,
            ]
        ];
        $availableTypes = [
            SharedCatalogInterface::TYPE_PUBLIC => __('Public'),
            SharedCatalogInterface::TYPE_CUSTOM => __('Custom')
        ];
        $this->sharedCatalog
            ->expects($this->any())
            ->method('getAvailableTypes')
            ->willReturn($availableTypes);
        $this->assertEquals($result, $this->sharedCatalogTypeMock->toOptionArray());
    }
}
