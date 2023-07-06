<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Team;

use Exception;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Controller\Team\Manage;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\Team;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Console\Request;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for Magento\Company\Controller\Team\Manage class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ManageTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var TeamRepositoryInterface|MockObject
     */
    private $teamRepository;

    /**
     * @var TeamInterfaceFactory|MockObject
     */
    private $teamFactory;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Manage
     */
    private $manage;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var TeamInterface|MockObject
     */
    private $team;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var DataObject|MockObject
     */
    private $dataObject;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->willReturn(
                [
                    'teams' => [1, 2, 5, 7],
                    'structures' => [1, 2]
                ]
            );
        $this->teamRepository = $this->getMockBuilder(TeamRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->team = $this->getMockBuilder(Team::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamFactory = $this->getMockBuilder(TeamInterfaceFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getCompanyAttributes', 'getCompanyId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->manage = $objectManagerHelper->getObject(
            Manage::class,
            [
                'resultFactory' => $this->resultFactory,
                'structureManager' => $this->structureManager,
                'teamRepository' => $this->teamRepository,
                'teamFactory' => $this->teamFactory,
                'objectHelper' => $this->dataObjectHelper,
                'logger' => $logger,
                '_request' => $this->request,
                'customerRepository' => $this->customerRepository,
                'companyContext' => $this->companyContext
            ]
        );
    }

    /**
     * Test 'execute' method.
     *
     * @return void
     */
    public function testExecuteWithTeamId(): void
    {
        $this->executeWithTeamId();
        $this->teamRepository->expects($this->once())
            ->method('save')
            ->with($this->team)
            ->willReturn($this->team);
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->teamRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->team);
        $this->team->expects($this->once())
            ->method('getData')
            ->willReturn([]);
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'ok',
                    'message' => __('The team was successfully updated.'),
                    'data' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Execute with team id.
     *
     * @return void
     */
    private function executeWithTeamId(): void
    {
        $params = [];
        $this->request->expects($this->any())
            ->method('getParam')
            ->with('team_id')
            ->willReturn(1);
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with(
                $this->team,
                $params,
                TeamInterface::class
            );
        $this->team->expects($this->once())
            ->method('setId')
            ->willReturn($this->team);
    }

    /**
     * Test 'execute' method wish forbidden team id.
     *
     * @return void
     */
    public function testExecuteWithForbiddenTeamId(): void
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->with('team_id')
            ->willReturn(999);
        $this->teamFactory->expects($this->never())
            ->method('create');
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('You are not allowed to do this.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Test 'execute' method with 'LocalizedException'.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException(): void
    {
        $this->executeWithTeamId();
        $exception = new LocalizedException(__('Something went wrong.'));
        $this->teamRepository->expects($this->once())
            ->method('save')
            ->with($this->team)
            ->willThrowException($exception);
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('Something went wrong.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Test 'execute' method with 'Exception'.
     *
     * @return void
     */
    public function testExecuteWithTeamIdWithException(): void
    {
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->executeWithTeamId();
        $this->teamRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('Something went wrong.'));
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('Something went wrong.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Test 'execute' method.
     *
     * @return void
     */
    public function testExecuteWithEmptyTeamId(): void
    {
        $this->createSetUp();
        $this->structureManager->expects($this->never())
            ->method('moveNode')
            ->willReturnSelf();
        $this->executeWithEmptyTeamId();
        $this->endExecuteWithEmptyTeamId();
    }

    /**
     * Test 'execute' method.
     *
     * @return void
     */
    public function testExecuteWithEmptyTeamIdAndNotEmptyTargetId(): void
    {
        $this->createSetUp();
        $structure = $this->getMockForStructure();
        $structure->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->structureManager->expects($this->once())
            ->method('getStructureByTeamId')
            ->willReturn($structure);
        $this->structureManager->expects($this->once())
            ->method('moveNode')
            ->willReturn('4');
        $this->executeWithEmptyTeamId(1);
        $this->endExecuteWithEmptyTeamId();
    }

    /**
     * Common part for 'testExecuteWithEmptyTeamId' and 'testExecuteWithEmptyTeamIdAndNotEmptyTargetId' methods.
     *
     * @return void
     */
    private function endExecuteWithEmptyTeamId(): void
    {
        $newTeamId = 2;
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->teamRepository->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($newTeamId);
        $this->team->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn([]);
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'ok',
                    'message' => __('The team was successfully created.'),
                    'data' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Execute with empty team id.
     *
     * @param int|null $targetId
     *
     * @return void
     */
    private function executeWithEmptyTeamId(?int $targetId = null): void
    {
        $params = [];
        $structure = $this->getMockForStructure();
        $this->structureManager->expects($this->never())
            ->method('getStructureByCustomerId')
            ->willReturn($structure);
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['team_id'], ['target_id'])
            ->willReturnOnConsecutiveCalls('', $targetId ?? '');
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with($this->team, $params, TeamInterface::class);
    }

    /**
     * testExecuteWithForbiddenStructureId.
     *
     * @return void
     */
    public function testExecuteWithForbiddenStructureId(): void
    {
        $targetId = 999;
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['team_id'], ['target_id'])
            ->willReturnOnConsecutiveCalls('', $targetId);
        $this->structureManager->expects($this->never())
            ->method('getStructureByCustomerId');
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('You are not allowed to do this.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Test 'execute' method with 'LocalizedException'.
     *
     * @return void
     */
    public function testCreateWithLocalizedException(): void
    {
        $exception = new LocalizedException(__('Something went wrong.'));
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->structureManager->expects($this->never())
            ->method('getStructureByCustomerId')
            ->willReturn(null);
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('Something went wrong.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Test 'create' method with 'Exception'.
     *
     * @return void
     */
    public function testCreateWithException(): void
    {
        $exception = new Exception('Something went wrong.');
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->structureManager->expects($this->never())
            ->method('getStructureByCustomerId')
            ->willReturn(null);
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'message' => __('Something went wrong.'),
                    'payload' => []
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->manage->execute());
    }

    /**
     * Get mock for structure.
     *
     * @return MockObject
     */
    private function getMockForStructure(): MockObject
    {
        return $this
            ->getMockBuilder(StructureInterface::class)
            ->onlyMethods([
                'getId',
                'getParentId',
                'getEntityId',
                'getEntityType',
                'getPath',
                'getPosition',
                'getLevel',
                'setId',
                'setParentId',
                'setEntityId',
                'setEntityType',
                'setPath',
                'setPosition',
                'setLevel'
            ])
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * createSetUp.
     *
     * @return void
     */
    private function createSetUp(): void
    {
        $this->teamFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->team);
        $this->dataObject->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturn($this->dataObject);
        $this->dataObject->expects($this->once())
            ->method('getCompanyId')
            ->willReturn(1);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->dataObject);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->willReturn($customer);

        $this->companyContext->expects($this->atLeastOnce())
            ->method('getCustomerId')
            ->willReturn(1);
    }
}
