<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\HierarchyInterface;
use Magento\Company\Api\Data\HierarchyInterfaceFactory;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyHierarchy;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\CompanyHierarchy class.
 */
class CompanyHierarchyTest extends TestCase
{
    /**
     * @var HierarchyInterfaceFactory|MockObject
     */
    private $hierarchyFactory;

    /**
     * @var Structure|MockObject
     */
    private $structure;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanyHierarchy
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->hierarchyFactory = $this->getMockBuilder(HierarchyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->structure = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CompanyHierarchy::class,
            [
                'hierarchyFactory' => $this->hierarchyFactory,
                'structure' => $this->structure,
                'companyRepository' => $this->companyRepository,
            ]
        );
    }

    /**
     * Test moveNode method.
     *
     * @return void
     */
    public function testMoveNode()
    {
        $id = 2;
        $newParentId = 5;
        $this->structure->expects($this->once())->method('moveNode')->with($id, $newParentId);

        $this->model->moveNode($id, $newParentId);
    }

    /**
     * Test getCompanyHierarchy method.
     *
     * @param string $structureType
     * @param string $hierarchyType
     * @return void
     * @dataProvider getCompanyHierarchyDataProvider
     */
    public function testGetCompanyHierarchy($structureType, $hierarchyType)
    {
        $id = 2;
        $superUserId = 3;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tree = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $treeCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hierarchy = $this->getMockBuilder(HierarchyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository->expects($this->once())->method('get')->with($id)->willReturn($company);
        $company->expects($this->once())->method('getSuperUserId')->willReturn($superUserId);
        $this->structure->expects($this->once())
            ->method('getTreeByCustomerId')
            ->with($superUserId)
            ->willReturn($tree);
        $tree->expects($this->atLeastOnce())->method('hasChildren')->willReturnOnConsecutiveCalls(true, false);
        $tree->expects($this->once())->method('getChildren')->willReturn(new \ArrayIterator($treeCollection));
        $tree->expects($this->atLeastOnce())
            ->method('getData')
            ->withConsecutive(['structure_id'], ['parent_id'], ['entity_id'], ['entity_type'])
            ->willReturnOnConsecutiveCalls(4, 3, 5, $structureType);
        $this->hierarchyFactory->expects($this->once())
            ->method('create')
            ->with(
                [
                    'data' => [
                        'structure_id' => 4,
                        'structure_parent_id' => 3,
                        'entity_id' => 5,
                        'entity_type' => $hierarchyType
                    ]
                ]
            )
            ->willReturn($hierarchy);

        $this->assertSame([$hierarchy], $this->model->getCompanyHierarchy($id));
    }

    /**
     * Data provider for getCompanyHierarchy method.
     *
     * @return array
     */
    public function getCompanyHierarchyDataProvider()
    {
        return [
            [
                StructureInterface::TYPE_CUSTOMER,
                HierarchyInterface::TYPE_CUSTOMER
            ],
            [
                StructureInterface::TYPE_TEAM,
                HierarchyInterface::TYPE_TEAM
            ],
        ];
    }

    /**
     * Test getCompanyHierarchy method with empty tree.
     *
     * @return void
     */
    public function testGetCompanyHierarchyWithEmptyTree()
    {
        $id = 2;
        $superUserId = 3;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository->expects($this->once())->method('get')->with($id)->willReturn($company);
        $company->expects($this->once())->method('getSuperUserId')->willReturn($superUserId);
        $this->structure->expects($this->once())
            ->method('getTreeByCustomerId')
            ->with($superUserId)
            ->willReturn(null);

        $this->assertEquals([], $this->model->getCompanyHierarchy($id));
    }
}
