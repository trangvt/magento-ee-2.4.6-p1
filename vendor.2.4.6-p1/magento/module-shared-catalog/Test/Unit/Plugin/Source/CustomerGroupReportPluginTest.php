<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Plugin\Source\CustomerGroupReportPlugin;
use Magento\SharedCatalog\Plugin\Source\SharedCatalogGroupsProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CustomerGroupReportPlugin plugin.
 */
class CustomerGroupReportPluginTest extends TestCase
{
    /**
     * @var CustomerGroupReportPlugin|MockObject
     */
    private $sharedCatalogGroupsProcessor;

    /**
     * @var CustomerGroupReportPlugin
     */
    private $customerGroupReportPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sharedCatalogGroupsProcessor = $this
            ->getMockBuilder(SharedCatalogGroupsProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->customerGroupReportPlugin = $objectManager->getObject(
            CustomerGroupReportPlugin::class,
            [
                'sharedCatalogGroupsProcessor' => $this->sharedCatalogGroupsProcessor,
            ]
        );
    }

    /**
     * Test for afterToOptionArray method.
     *
     * @return void
     */
    public function testAfterToOptionArray()
    {
        $groups = [1 => 'Customer Group 1', 2 => 'Customer Group 2'];
        $source = $this->getMockBuilder(OptionSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogGroupsProcessor->expects($this->once())->method('prepareGroups')->willReturnArgument(0);

        $this->assertEquals(
            [2 => ['label' => 'Customer Group 2', 'value' => 2], 1 => ['label' => 'Customer Group 1', 'value' => 1]],
            $this->customerGroupReportPlugin->afterToOptionArray($source, $groups)
        );
    }
}
