<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrderRule\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as DBAdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test For Rule Resource Model for PurchaseOrderRule module
 *
 * @see Rule
 */
class RuleTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourcesMock;

    /**
     * @var DBAdapterInterface|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var Rule
     */
    private $ruleResourceModel;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->resourcesMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTableName', 'getConnection'])
            ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertOnDuplicate', 'delete'])
            ->getMock();

        $this->resourcesMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->resourceConnectionMock);

        $this->ruleResourceModel = $objectManagerHelper->getObject(Rule::class, [
            '_resources' => $this->resourcesMock
        ]);
    }

    public function testSaveAppliesTo()
    {
        $this->resourcesMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('purchase_order_rule_applies_to')
            ->willReturn('prefix_purchase_order_rule_applies_to');

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('insertOnDuplicate')
            ->with('prefix_purchase_order_rule_applies_to', ['rule_id' =>  1, 'role_id' => 2]);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('delete')
            ->with('prefix_purchase_order_rule_applies_to', ['rule_id = ?' =>  1, 'role_id NOT IN (?)' => [2]]);

        $this->ruleResourceModel->saveAppliesTo(1, [2]);
    }

    public function testSaveApproverRolesIds()
    {
        $this->resourcesMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('purchase_order_rule_approver')
            ->willReturn('prefix_purchase_order_rule_approver');

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('insertOnDuplicate')
            ->with('prefix_purchase_order_rule_approver', ['rule_id' =>  1, 'role_id' => 2]);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('delete')
            ->with('prefix_purchase_order_rule_approver', ['rule_id = ?' =>  1, 'role_id NOT IN (?)' => [2]]);

        $this->ruleResourceModel->saveApproverRoleIds(1, [2]);
    }

    public function testSetAdminApprovalRequired()
    {
        $this->resourcesMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('purchase_order_rule_approver')
            ->willReturn('prefix_purchase_order_rule_approver');

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('delete')
            ->with(
                'prefix_purchase_order_rule_approver',
                [
                    'rule_id = ?' =>  2,
                    'role_id IS NULL',
                    'requires_admin_approval = 1'
                ]
            );

        $this->ruleResourceModel->setAdminApprovalRequired(2, false);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                'prefix_purchase_order_rule_approver',
                [
                    'rule_id' =>  2,
                    'requires_admin_approval' => 1
                ]
            );

        $this->ruleResourceModel->setAdminApprovalRequired(2, true);
    }

    public function testSetManagerApprovalRequired()
    {
        $this->resourcesMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('purchase_order_rule_approver')
            ->willReturn('prefix_purchase_order_rule_approver');

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('delete')
            ->with(
                'prefix_purchase_order_rule_approver',
                [
                    'rule_id = ?' =>  2,
                    'role_id IS NULL',
                    'requires_manager_approval = 1'
                ]
            );

        $this->ruleResourceModel->setManagerApprovalRequired(2, false);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                'prefix_purchase_order_rule_approver',
                [
                    'rule_id' =>  2,
                    'requires_manager_approval' => 1
                ]
            );

        $this->ruleResourceModel->setManagerApprovalRequired(2, true);
    }
}
