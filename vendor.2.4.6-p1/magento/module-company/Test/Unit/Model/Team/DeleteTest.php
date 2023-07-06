<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Team;

use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\ResourceModel\Team as TeamResource;
use Magento\Company\Model\StructureRepository;
use Magento\Company\Model\Team;
use Magento\Company\Model\Team\Delete;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Model\Team\Delete class.
 */
class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $deleteCommand;

    /**
     * @var TeamResource|MockObject
     */
    private $teamResourceMock;

    /**
     * @var StructureRepository|MockObject
     */
    private $structureRepositoryMock;

    /**
     * @var Structure|MockObject
     */
    private $structureManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->teamResourceMock = $this->getMockBuilder(TeamResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureRepositoryMock = $this->getMockBuilder(StructureRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManagerMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deleteCommand = (new ObjectManager($this))->getObject(
            Delete::class,
            [
                'teamResource' => $this->teamResourceMock,
                'structureRepository' => $this->structureRepositoryMock,
                'structureManager' => $this->structureManagerMock
            ]
        );
    }

    /**
     * Test for `delete` method.
     *
     * @return void
     */
    public function testDelete()
    {
        $structureId = 2;

        $structure = $this->getMockBuilder(StructureInterface::class)
            ->getMockForAbstractClass();
        $structure->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($structureId);
        $this->structureManagerMock->expects($this->atLeastOnce())
            ->method('getStructureByTeamId')
            ->willReturn($structure);

        $this->structureRepositoryMock->expects($this->once())
            ->method('deleteById')
            ->with($structureId);

        $team = $this->getMockBuilder(Team::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->teamResourceMock->expects($this->once())
            ->method('delete')
            ->with($team);

        $this->deleteCommand->delete($team);
    }

    /**
     * Test for `delete` method with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('This team has child users or teams aligned to it and cannot be deleted.');
        $teamId = 1;
        $structureId = 2;

        $structure = $this->getMockBuilder(StructureInterface::class)
            ->getMockForAbstractClass();
        $structure->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($structureId);
        $this->structureManagerMock->expects($this->atLeastOnce())
            ->method('getStructureByTeamId')
            ->with($teamId)
            ->willReturn($structure);

        $node = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->atLeastOnce())
            ->method('hasChildren')
            ->willReturn(true);
        $this->structureManagerMock->expects($this->atLeastOnce())
            ->method('getTreeById')
            ->with($structureId)
            ->willReturn($node);

        $this->structureRepositoryMock->expects($this->never())
            ->method('deleteById');

        $this->teamResourceMock->expects($this->never())
            ->method('delete');

        $team = $this->getMockBuilder(Team::class)
            ->disableOriginalConstructor()
            ->getMock();
        $team->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($teamId);

        $this->deleteCommand->delete($team);
    }
}
