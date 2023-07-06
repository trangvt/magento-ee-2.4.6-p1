<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\Move;
use Magento\RequisitionList\Model\Action\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\RequisitionList\Controller\Item\Move class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MoveTest extends TestCase
{
    /**
     * @var Move
     */
    private $move;

    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidatorMock;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepositoryMock;

    /**
     * @var RequisitionListManagementInterface|MockObject
     */
    private $requisitionListManagementMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestValidatorMock = $this->getMockBuilder(RequestValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListRepositoryMock = $this->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListManagementMock = $this->getMockBuilder(RequisitionListManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->move = (new ObjectManager($this))->getObject(
            Move::class,
            [
                'requestValidator' => $this->requestValidatorMock,
                'requisitionListRepository' => $this->requisitionListRepositoryMock,
                'requisitionListManagement' => $this->requisitionListManagementMock,
                'resultFactory' => $this->resultFactoryMock,
                '_request' => $this->requestMock,
                'messageManager' => $this->messageManagerMock
            ]
        );
    }

    /**
     * Test for `execute` method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->setUpMoveToList();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage');

        $this->move->execute();
    }

    /**
     * Test for `execute` method with failed validation.
     *
     * @return void
     */
    public function testExecuteWithValidationFailed()
    {
        $resultMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestValidatorMock->expects($this->atLeastOnce())
            ->method('getResult')
            ->willReturn($resultMock);

        $this->assertEquals(
            $resultMock,
            $this->move->execute()
        );
    }

    /**
     * Test for `execute` method with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntity()
    {
        $targetListId = 1;
        $sourceListId = 2;
        $selected = '1,2,3';

        $this->requestValidatorMock->expects($this->atLeastOnce())
            ->method('getResult')
            ->willReturn(null);

        $resultMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturnMap([
                ['list_id', null, $targetListId],
                ['source_list_id', null, $sourceListId],
                ['selected', null, $selected],
            ]);

        $this->requisitionListRepositoryMock->expects($this->atLeastOnce())
            ->method('get')
            ->with($sourceListId)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $this->move->execute();
    }

    /**
     * Test for `execute` method with CouldNotSaveException.
     *
     * @return void
     */
    public function testExecuteWithCouldNotSaveException()
    {
        $this->setUpMoveToList();

        $this->requisitionListRepositoryMock->expects($this->atLeastOnce())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(__('Could not save')));

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $this->move->execute();
    }

    /**
     * Build item mock.
     *
     * @param int $id
     * @return MockObject
     */
    private function buildItemMock($id)
    {
        $itemMock = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($id);

        return $itemMock;
    }

    /**
     * Set up move to list.
     *
     * @return void
     */
    private function setUpMoveToList()
    {
        $targetListId = 1;
        $sourceListId = 2;
        $selected = '1,2,3';

        $this->requestValidatorMock->expects($this->atLeastOnce())
            ->method('getResult')
            ->willReturn(null);

        $resultMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultMock);

        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturnMap([
                ['list_id', null, $targetListId],
                ['source_list_id', null, $sourceListId],
                ['selected', null, $selected],
            ]);

        $sourceListMock = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $targetListMock = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListRepositoryMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [$sourceListId, $sourceListMock],
                [$targetListId, $targetListMock],
            ]);

        $items = [
            $this->buildItemMock(1),
            $this->buildItemMock(2),
            $this->buildItemMock(3),
            $this->buildItemMock(4),
        ];
        $sourceListMock->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn($items);

        $this->requisitionListManagementMock->expects($this->exactly(3))
            ->method('copyItemToList')
            ->withConsecutive(
                [$targetListMock, $items[0]],
                [$targetListMock, $items[1]],
                [$targetListMock, $items[2]]
            );
    }
}
