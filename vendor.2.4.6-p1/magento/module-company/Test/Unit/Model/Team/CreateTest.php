<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Team;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\ResourceModel\Team as TeamResource;
use Magento\Company\Model\Team;
use Magento\Company\Model\Team\Create;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Model\Team\Create class.
 */
class CreateTest extends TestCase
{
    /**
     * @var Create
     */
    private $createCommand;

    /**
     * @var TeamResource|MockObject
     */
    private $teamResourceMock;

    /**
     * @var Structure|MockObject
     */
    private $structureManagerMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->teamResourceMock = $this->getMockBuilder(TeamResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManagerMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->createCommand = (new ObjectManager($this))->getObject(
            Create::class,
            [
                'teamResource' => $this->teamResourceMock,
                'structureManager' => $this->structureManagerMock,
                'companyRepository' => $this->companyRepositoryMock
            ]
        );
    }

    /**
     * Test for `create` method.
     *
     * @return void
     */
    public function testCreate()
    {
        $companyId = 1;
        $superUserId = 2;
        $teamId = 3;
        $nodeId = 4;

        $team = $this->getMockBuilder(Team::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $team->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturnOnConsecutiveCalls(null, $teamId);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->getMockForAbstractClass();
        $company->expects($this->atLeastOnce())
            ->method('getSuperUserId')
            ->willReturn($superUserId);
        $this->companyRepositoryMock->expects($this->atLeastOnce())
            ->method('get')
            ->with($companyId)
            ->willReturn($company);

        $tree = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tree->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($nodeId);
        $this->structureManagerMock->expects($this->atLeastOnce())
            ->method('getTreeByCustomerId')
            ->with($superUserId)
            ->willReturn($tree);

        $this->structureManagerMock->expects($this->once())
            ->method('addNode')
            ->with(
                $teamId,
                StructureInterface::TYPE_TEAM,
                $nodeId
            );

        $this->createCommand->create($team, $companyId);
    }

    /**
     * Test for `create` method with exception.
     *
     * @return void
     */
    public function testCreateWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not create team');
        $team = $this->getMockBuilder(Team::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $team->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->createCommand->create($team, 1);
    }
}
