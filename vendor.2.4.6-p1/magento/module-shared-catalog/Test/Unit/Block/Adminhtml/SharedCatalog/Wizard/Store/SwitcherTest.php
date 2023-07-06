<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\Store;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Store\Switcher;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Store\Switcher.
 */
class SwitcherTest extends TestCase
{
    /**
     * @var Store|MockObject
     */
    private $systemStore;

    /**
     * @var EncoderInterface|MockObject
     */
    private $jsonEncoder;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var GroupInterface|MockObject
     */
    private $storeGroup;

    /**
     * @var Switcher|MockObject
     */
    private $switcherMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getParam']
        );
        $this->sharedCatalogRepository = $this->getMockForAbstractClass(
            SharedCatalogRepositoryInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['get']
        );
        $this->sharedCatalog = $this->getMockForAbstractClass(
            SharedCatalogInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStoreId']
        );
        $this->systemStore = $this->createPartialMock(Store::class, ['getGroupCollection']);
        $this->storeGroup = $this->getMockForAbstractClass(
            GroupInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId', 'getName']
        );
        $this->jsonEncoder = $this->getMockForAbstractClass(EncoderInterface::class);
        $this->objectManager = new ObjectManager($this);

        $this->switcherMock = $this->objectManager->getObject(
            Switcher::class,
            [
                'systemStore' => $this->systemStore,
                'jsonEncoder' => $this->jsonEncoder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'data' => [],
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for isOptionSelected().
     *
     * @return void
     */
    public function testIsOptionSelected()
    {
        $id = 3654;
        $sharedCatalogParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogParam)->willReturn($id);

        $storeId = 34;
        $this->sharedCatalog->expects($this->exactly(1))->method('getStoreId')->willReturn($storeId);

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->with($id)
            ->willReturn($this->sharedCatalog);

        $expects = true;
        $this->assertEquals($expects, $this->switcherMock->isOptionSelected());
    }

    /**
     * Test for getSelectedOptionLabel().
     *
     * @param int $storeGroupId
     * @param string|null $expectedResult
     * @dataProvider getSelectedOptionLabelDataProvider
     * @return void
     */
    public function testGetSelectedOptionLabel($storeGroupId, $expectedResult)
    {
        $id = 3654;
        $sharedCatalogParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogParam)->willReturn($id);

        $storeId = 34;
        $this->sharedCatalog->expects($this->exactly(1))->method('getStoreId')->willReturn($storeId);

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->with($id)
            ->willReturn($this->sharedCatalog);

        $storeGroupName = 'All Stores';
        $this->storeGroup->expects($this->exactly(1))->method('getName')->willReturn($storeGroupName);
        $this->storeGroup->expects($this->exactly(1))->method('getId')->willReturn($storeGroupId);

        $storeGroups = [$this->storeGroup];
        $this->systemStore->expects($this->exactly(1))->method('getGroupCollection')->willReturn($storeGroups);

        $actualResult = $this->switcherMock->getSelectedOptionLabel();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Data provider for getSelectedOptionLabel() test.
     *
     * @return array
     */
    public function getSelectedOptionLabelDataProvider()
    {
        return [
            [45, null],
            [34, 'All Stores']
        ];
    }
}
