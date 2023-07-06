<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogBuilder;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model SharedCatalogBuilder.
 */
class SharedCatalogBuilderTest extends TestCase
{
    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogFactory|MockObject
     */
    private $sharedCatalogFactory;

    /**
     * @var SharedCatalog|MockObject
     */
    private $sharedCatalog;

    /**
     * @var SharedCatalogBuilder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogFactory = $this->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->builder = $objectManager->getObject(
            SharedCatalogBuilder::class,
            [
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sharedCatalogFactory' => $this->sharedCatalogFactory,
            ]
        );
    }

    /**
     * Test method build without id.
     *
     * @return void
     */
    public function testBuildNewCatalog()
    {
        $this->sharedCatalogRepository->expects($this->never())->method('get');
        $this->sharedCatalogFactory->expects($this->once())->method('create')->willReturn($this->sharedCatalog);
        $this->builder->build();
    }

    /**
     * Test method build with id.
     *
     * @return void
     */
    public function testBuildOldCatalog()
    {
        $this->sharedCatalogRepository->expects($this->once())->method('get')->willReturn($this->sharedCatalog);
        $this->assertEquals($this->sharedCatalog, $this->builder->build(1));
    }
}
