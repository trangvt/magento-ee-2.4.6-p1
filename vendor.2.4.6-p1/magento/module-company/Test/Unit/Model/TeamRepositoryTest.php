<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Model\ResourceModel\Team as ResourceModelTeam;
use Magento\Company\Model\ResourceModel\Team\Collection;
use Magento\Company\Model\Team;
use Magento\Company\Model\Team\Create;
use Magento\Company\Model\Team\Delete;
use Magento\Company\Model\Team\GetList;
use Magento\Company\Model\TeamFactory;
use Magento\Company\Model\TeamRepository;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Magento\Company\Model\TeamRepository class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TeamRepositoryTest extends TestCase
{
    /**
     * @var TeamRepository
     */
    private $teamRepository;

    /**
     * @var TeamFactory|MockObject
     */
    private $teamFactory;

    /**
     * @var ResourceModelTeam|MockObject
     */
    private $teamResource;

    /**
     * @var Delete|MockObject
     */
    private $teamDeleter;

    /**
     * @var Team|MockObject
     */
    private $team;

    /**
     * @var Collection|MockObject
     */
    private $teamCollection;

    /**
     * @var Create|MockObject
     */
    private $teamCreator;

    /**
     * @var GetList|MockObject
     */
    private $getLister;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->teamFactory = $this->getMockBuilder(TeamFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->team = $this->getMockBuilder(Team::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamResource = $this->getMockBuilder(ResourceModelTeam::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamDeleter = $this->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamCreator = $this->getMockBuilder(Create::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getLister = $this->getMockBuilder(GetList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);

        $this->teamRepository = $objectManagerHelper->getObject(
            TeamRepository::class,
            [
                'teamFactory' => $this->teamFactory,
                'teamResource' => $this->teamResource,
                'teamDeleter' => $this->teamDeleter,
                'teamCreator' => $this->teamCreator,
                'getLister' => $this->getLister,
            ]
        );
    }

    /**
     * Test for 'create' method.
     *
     * @param int $companyId
     * @param string $fieldName
     * @return void
     *
     * @dataProvider testCreateDataProvider
     */
    public function testCreate($companyId, $fieldName)
    {
        $this->team->expects($this->once())->method('getName')->willReturn($fieldName);
        $this->teamCreator->expects($this->once())->method('create')->willReturnSelf();
        $this->teamRepository->create($this->team, $companyId);
    }

    /**
     * Test for 'create' method with 'LocalizedException'.
     *
     * @return void
     */
    public function testCreateWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Could not create team');
        $testFiledName = 'test_field_name';
        $this->team->expects($this->once())->method('getName')->willReturn($testFiledName);
        $exception = new LocalizedException(__('Could not create team'));
        $this->teamCreator
            ->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->teamRepository->create($this->team, 1);
    }

    /**
     * Data provider for 'testGetList' method.
     *
     * @return array
     */
    public function testCreateDataProvider()
    {
        return [
            [1, 'test_field_name'],
        ];
    }

    /**
     * Test for 'create' method with 'CouldNotSaveException'.
     *
     * @return void
     */
    public function testCreateWithCouldNotSaveException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Could not create team');
        $testFiledName = 'test_field_name';
        $this->team->expects($this->once())->method('getName')->willReturn($testFiledName);
        $exception = new \Exception('Could not create team');
        $this->teamCreator
            ->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->teamRepository->create($this->team, 1);
    }

    /**
     * Test for 'save' method.
     *
     * @param int $id
     * @param string $fieldName
     * @return void
     *
     * @dataProvider testCreateDataProvider
     */
    public function testSave($id, $fieldName)
    {
        $this->team->expects($this->once())->method('getName')->willReturn($fieldName);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);
        $this->teamFactory->expects($this->once())->method('create')->willReturn($this->team);
        $this->teamResource->expects($this->once())->method('save')->willReturn($this->team);
        $this->teamRepository->save($this->team);
    }

    /**
     * Test for 'save' method with 'LocalizedException'.
     *
     * @return void
     */
    public function testSaveWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('"name" is required. Enter and try again.');
        $this->team->expects($this->once())->method('getName')->willReturn(false);
        $this->teamRepository->save($this->team);
    }

    /**
     * Test for 'save' method with 'CouldNotSaveException'.
     *
     * @return void
     */
    public function testSaveWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not update team');
        $testFiledName = 'test_field_name';
        $this->team->expects($this->once())->method('getName')->willReturn($testFiledName);
        $this->teamFactory->expects($this->once())->method('create')->willReturn($this->team);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $exceptionMessage = 'Could not save team attributes';
        $exception = new CouldNotSaveException(__($exceptionMessage));
        $this->teamResource
            ->expects($this->once())
            ->method('save')
            ->willThrowException($exception);
        $this->teamRepository->save($this->team);
    }

    /**
     * Test for 'checkRequiredFields' method with 'CouldNotSaveException'.
     *
     * @return void
     */
    public function testCheckRequiredFieldsWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('"id" is required. Enter and try again.');
        $testFiledName = 'test_field_name';
        $this->team->expects($this->once())->method('getName')->willReturn($testFiledName);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->teamRepository->save($this->team);
    }

    /**
     * Test for 'get' method.
     *
     * @return void
     */
    public function testGet()
    {
        $id = 1;
        $this->team->expects($this->once())->method('load')->willReturn($this->team);
        $this->teamFactory->expects($this->once())->method('create')->willReturn($this->team);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);

        $this->assertEquals($this->team, $this->teamRepository->get($id));
    }

    /**
     * Test for 'save' method with 'Exception'.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $id = null;
        $this->team->expects($this->once())->method('load')->willReturn($this->team);
        $this->teamFactory->expects($this->once())->method('create')->willReturn($this->team);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);

        $this->assertEquals($this->team, $this->teamRepository->get($id));
    }

    /**
     * Test for 'delete' method.
     *
     * @return void
     */
    public function testDelete()
    {
        $id = 1;
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);
        $this->teamDeleter->expects($this->once())->method('delete')->willReturn($this->team);
        $this->teamRepository->delete($this->team);
    }

    /**
     * Test for 'delete' method with 'LocalizedException'.
     *
     * @return void
     */
    public function testDeleteWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $exception = new LocalizedException(__('Cannot delete team'));
        $this->teamDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->team)
            ->willThrowException($exception);
        $this->teamRepository->delete($this->team);
    }

    /**
     * Test for 'delete' method with 'Exception'.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Cannot delete team with id 1');
        $id = 1;
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);
        $exception = new \Exception('Cannot delete team');
        $this->teamDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->team)
            ->willThrowException($exception);
        $this->teamRepository->delete($this->team);
    }

    /**
     * Test for 'deleteById' method.
     *
     * @return void
     */
    public function testDeleteById()
    {
        $id = 1;
        $this->team->expects($this->once())->method('load')->willReturn($this->team);
        $this->teamFactory->expects($this->once())->method('create')->willReturn($this->team);
        $this->team->expects($this->atLeastOnce())->method('getId')->willReturn($id);
        $this->teamDeleter->expects($this->once())->method('delete')->willReturn($this->team);
        $this->teamRepository->deleteById($id);
    }

    /**
     * Test for 'getList' method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->getMock();
        $this->getLister->expects($this->once())->method('getList')->with($searchCriteria)->willReturnSelf();
        $this->teamRepository->getList($searchCriteria);
    }
}
