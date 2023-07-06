<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\State.
 */
class StateTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var State|MockObject
     */
    private $state;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->state = $this->objectManagerHelper->getObject(
            State::class,
            [
                'context' => $this->contextMock,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for isCatalogConfigured().
     *
     * @return void
     */
    public function testIsCatalogConfigured()
    {
        $storeId = 3;
        $this->sharedCatalog->expects($this->exactly(1))->method('getStoreId')->willReturn($storeId);

        $sharedCatalogParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $catalogId = 4676;
        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogParam)
            ->willReturn($catalogId);

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->with($catalogId)
            ->willReturn($this->sharedCatalog);

        $expects = true;
        $this->assertEquals($expects, $this->state->isCatalogConfigured());
    }
}
