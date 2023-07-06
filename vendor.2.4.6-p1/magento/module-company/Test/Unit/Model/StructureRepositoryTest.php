<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\StructureSearchResultsInterface;
use Magento\Company\Model\ResourceModel\Structure;
use Magento\Company\Model\Structure\SearchProvider;
use Magento\Company\Model\StructureFactory;
use Magento\Company\Model\StructureRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for StructureRepository model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StructureRepositoryTest extends TestCase
{
    /**
     * @var StructureFactory|MockObject
     */
    private $structureFactory;

    /**
     * @var Structure|MockObject
     */
    private $structureResource;

    /**
     * @var SearchProvider|MockObject
     */
    private $searchProvider;

    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->structureFactory = $this->getMockBuilder(StructureFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureResource = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchProvider = $this
            ->getMockBuilder(SearchProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->structureRepository = $objectManager->getObject(
            StructureRepository::class,
            [
                'structureFactory' => $this->structureFactory,
                'structureResource' => $this->structureResource,
                'searchProvider' => $this->searchProvider,
            ]
        );
    }

    /**
     * Test for save method.
     *
     * @return void
     */
    public function testSave()
    {
        $structureId = 1;
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureResource->expects($this->once())->method('save')->with($structure)->willReturnSelf();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn($structureId);
        $this->assertEquals($structureId, $this->structureRepository->save($structure));
    }

    /**
     * Test for save method with exception.
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save company');
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureResource->expects($this->once())
            ->method('save')->with($structure)->willThrowException(new \Exception());
        $this->structureRepository->save($structure);
    }

    /**
     * Test for get method.
     *
     * @return void
     */
    public function testGet()
    {
        $structureId = 1;
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureFactory->expects($this->once())->method('create')->willReturn($structure);
        $structure->expects($this->once())->method('load')->with($structureId)->willReturnSelf();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn($structureId);
        $this->assertEquals($structure, $this->structureRepository->get($structureId));
    }

    /**
     * Test for get method with exception.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with id = 1');
        $structureId = 1;
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureFactory->expects($this->once())->method('create')->willReturn($structure);
        $structure->expects($this->once())->method('load')->with($structureId)->willReturnSelf();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->structureRepository->get($structureId);
    }

    /**
     * Test for delete method.
     *
     * @return void
     */
    public function testDelete()
    {
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->structureResource->expects($this->once())->method('delete')->with($structure)->willReturnSelf();
        $this->assertTrue($this->structureRepository->delete($structure));
    }

    /**
     * Test for delete method with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage('Cannot delete structure with id 1');
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->structureResource->expects($this->once())
            ->method('delete')->with($structure)->willThrowException(new \Exception());
        $this->structureRepository->delete($structure);
    }

    /**
     * Test for deleteById method.
     *
     * @return void
     */
    public function testDeleteById()
    {
        $structureId = 1;
        $structure = $this->getMockBuilder(\Magento\Company\Model\Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $structure->expects($this->atLeastOnce())->method('getId')->willReturn($structureId);
        $this->structureFactory->expects($this->once())->method('create')->willReturn($structure);
        $structure->expects($this->once())->method('load')->with($structureId)->willReturnSelf();
        $this->structureResource->expects($this->once())->method('delete')->with($structure)->willReturnSelf();
        $this->assertTrue($this->structureRepository->deleteById($structureId));
    }

    /**
     * Test for getList method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(StructureSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchProvider->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $this->assertEquals($searchResults, $this->structureRepository->getList($searchCriteria));
    }
}
