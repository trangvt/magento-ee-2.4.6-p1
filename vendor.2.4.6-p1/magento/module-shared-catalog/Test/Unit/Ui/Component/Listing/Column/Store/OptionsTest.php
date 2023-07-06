<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Store;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Store\Options;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Options|MockObject
     */
    protected $optionsMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $groupName = 'test name';
        $id = 123; //test id
        $group = $this->createPartialMock(
            Group::class,
            ['getId', 'getName']
        );
        $group->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($id);
        $group->expects($this->exactly(3))
            ->method('getName')
            ->willReturn($groupName);
        $groups = [$group];
        $website = $this->getMockForAbstractClass(
            WebsiteInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getGroups']
        );
        $website->expects($this->once())
            ->method('getGroups')
            ->willReturn($groups);
        $websites = [$website];
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getWebsites']
        );
        $this->storeManager->expects($this->exactly(2))
            ->method('getWebsites')
            ->willReturn($websites);
        $objectManager = new ObjectManager($this);
        $this->optionsMock = $objectManager->getObject(
            Options::class,
            [
                'storeManager' => $this->storeManager,
            ]
        );
    }

    public function testToOptionArray()
    {
        $this->optionsMock->toOptionArray();
    }
}
