<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Plugin\CatalogSearch\Model\ResourceModel;

use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\QuickOrder\Plugin\CatalogSearch\Model\ResourceModel\EngineInterfacePlugin;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EngineInterfacePlugin.
 */
class EngineInterfacePluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var EngineInterfacePlugin
     */
    private $engineInterfacePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->engineInterfacePlugin = $this->objectManagerHelper->getObject(EngineInterfacePlugin::class);
    }

    /**
     * @param array $result
     * @param array $expected
     * @dataProvider visibilityFixtureDataProvider
     * @see EngineInterfacePlugin::afterGetAllowedVisibility
     * @return void
     */
    public function testAfterGetAllowedVisibility($result, $expected)
    {
        $subject = $this->getMockBuilder(EngineInterface::class)
            ->getMock();

        $this->assertSame($expected, $this->engineInterfacePlugin->afterGetAllowedVisibility($subject, $result));
    }

    /**
     * Data provider for testAfterGetAllowedVisibility.
     *
     * @return array
     */
    public function visibilityFixtureDataProvider()
    {
        return [
            [[Visibility::VISIBILITY_NOT_VISIBLE], [Visibility::VISIBILITY_NOT_VISIBLE]],
            [[], [Visibility::VISIBILITY_NOT_VISIBLE]]
        ];
    }
}
