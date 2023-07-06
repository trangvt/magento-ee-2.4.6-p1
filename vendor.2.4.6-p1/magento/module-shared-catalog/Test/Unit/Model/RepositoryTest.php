<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SearchResultsInterfaceFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\Repository;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Magento\SharedCatalog\Model\SaveHandler;
use Magento\SharedCatalog\Model\SharedCatalogValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Repository unit test.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RepositoryTest extends TestCase
{
    /**
     * @var SharedCatalog|MockObject
     */
    private $sharedCatalogResource;

    /**
     * @var CollectionFactory|MockObject
     */
    private $sharedCatalogCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var ProductItemManagementInterface|MockObject
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessor;

    /**
     * @var SharedCatalogValidator|MockObject
     */
    private $validator;

    /**
     * @var SaveHandler|MockObject
     */
    private $saveHandler;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogResource =
            $this->createMock(SharedCatalog::class);
        $this->sharedCatalogCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->searchResultsFactory = $this->createPartialMock(
            SearchResultsInterfaceFactory::class,
            ['create']
        );
        $this->sharedCatalogProductItemManagement =
            $this->getMockForAbstractClass(ProductItemManagementInterface::class);
        $this->collectionProcessor =
            $this->getMockForAbstractClass(CollectionProcessorInterface::class);
        $this->validator = $this->createMock(SharedCatalogValidator::class);
        $this->saveHandler = $this->createMock(SaveHandler::class);
        $objectManager = new ObjectManager($this);
        $this->repository = $objectManager->getObject(
            Repository::class,
            [
                'sharedCatalogResource' => $this->sharedCatalogResource,
                'sharedCatalogCollectionFactory' => $this->sharedCatalogCollectionFactory,
                'searchResultsFactory' => $this->searchResultsFactory,
                'sharedCatalogProductItemManagement' => $this->sharedCatalogProductItemManagement,
                'collectionProcessor' => $this->collectionProcessor,
                'validator' => $this->validator,
                'saveHandler' => $this->saveHandler
            ]
        );
    }

    /**
     * Test save.
     *
     * @return void
     */
    public function testSave()
    {
        $id = 1;
        $sharedCatalog = $this->getMockForAbstractClass(
            SharedCatalogInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getData', 'setData', 'getId']
        );
        $sharedCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($id);
        $sharedCatalog->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $sharedCatalog->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $this->prepareMocksGet($sharedCatalog);
        $this->saveHandler->expects($this->once())->method('execute')->with($sharedCatalog)->willReturn($sharedCatalog);

        $this->assertEquals($id, $this->repository->save($sharedCatalog));
    }

    /**
     * Test get.
     *
     * @return void
     */
    public function testGet()
    {
        $sharedCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $this->prepareMocksGet($sharedCatalog);

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->repository->get(1)
        );
    }

    /**
     * Test delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $sharedCatalog = $this->createMock(\Magento\SharedCatalog\Model\SharedCatalog::class);
        $this->prepareMocksDelete($sharedCatalog);

        $this->assertTrue($this->repository->delete($sharedCatalog));
    }

    /**
     * Test deleteById.
     *
     * @return void
     */
    public function testDeleteById()
    {
        $sharedCatalog = $this->createMock(\Magento\SharedCatalog\Model\SharedCatalog::class);
        $this->prepareMocksGet($sharedCatalog);
        $this->prepareMocksDelete($sharedCatalog);

        $this->assertTrue($this->repository->deleteById(1));
    }

    /**
     * Test testGetList.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $searchResults->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $searchResults->expects($this->once())->method('setTotalCount')->willReturnSelf();
        $searchResults->expects($this->once())->method('setItems')->willReturnSelf();
        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResults);
        $sharedCatalogCollection =
            $this->createMock(Collection::class);
        $sharedCatalogCollection->expects($this->once())->method('getSize')->willReturn(1);
        $sharedCatalogCollection->expects($this->once())->method('getItems')->willReturn([]);
        $this->sharedCatalogCollectionFactory->expects($this->once())->method('create')
            ->willReturn($sharedCatalogCollection);
        $this->collectionProcessor->expects($this->once())->method('process');

        $this->assertInstanceOf(
            SearchResultsInterface::class,
            $this->repository->getList($searchCriteria)
        );
    }

    /**
     * Prepare mocks get.
     *
     * @param SharedCatalogInterface|MockObject $sharedCatalog
     * @return void
     */
    private function prepareMocksGet($sharedCatalog)
    {
        $sharedCatalogCollection =
            $this->createMock(Collection::class);
        $sharedCatalogCollection->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollection->expects($this->once())->method('getFirstItem')->willReturn($sharedCatalog);
        $this->sharedCatalogCollectionFactory->expects($this->once())->method('create')
            ->willReturn($sharedCatalogCollection);
    }

    /**
     * Prepare mocks delete.
     *
     * @param SharedCatalogInterface|MockObject $sharedCatalog
     * @return void
     */
    private function prepareMocksDelete($sharedCatalog)
    {
        $sharedCatalog->expects($this->once())->method('getId')->willReturn(1);
        $this->validator->expects($this->once())->method('isSharedCatalogPublic')->with($sharedCatalog)
            ->willReturn(true);
        $this->sharedCatalogProductItemManagement->expects($this->once())->method('deleteItems')->with($sharedCatalog);
        $this->sharedCatalogResource->expects($this->once())->method('delete');
    }
}
