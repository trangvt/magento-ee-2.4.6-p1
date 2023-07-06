<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Api\Data\HierarchyInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Model\StructureRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Structure data provider
 */
class Structure
{
    /**
     * @var int
     */
    private $allowedDepth;

    /**
     * @var int
     */
    private $depth = 0;

    /**
     * @var array
     */
    private $structure = [];

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ExtractCustomerData
     */
    private $customerData;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @param ExtractCustomerData $customerData
     * @param CustomerRepositoryInterface $customerRepository
     * @param Uid $idEncoder
     * @param StructureRepository $structureRepository
     */
    public function __construct(
        ExtractCustomerData $customerData,
        CustomerRepositoryInterface $customerRepository,
        Uid $idEncoder,
        StructureRepository $structureRepository
    ) {
        $this->customerData = $customerData;
        $this->customerRepository = $customerRepository;
        $this->idEncoder = $idEncoder;
        $this->structureRepository = $structureRepository;
    }

    /**
     * Get team parent customer structure.
     *
     * @param StructureInterface $structure
     * @return StructureInterface|null
     * @throws LocalizedException|NoSuchEntityException
     */
    public function getTeamParentCustomerStructure(StructureInterface $structure): ?StructureInterface
    {
        $entityType = $structure->getEntityType();
        if ((int)$entityType === StructureInterface::TYPE_CUSTOMER) {
            return $structure;
        }

        $parentId = $structure->getParentId();

        if ((int)$entityType === StructureInterface::TYPE_TEAM && $parentId) {
            return $this->getTeamParentCustomerStructure($this->structureRepository->get($parentId));
        }

        return null;
    }

    /**
     * Get formatted structure
     *
     * @param Node $tree
     * @param int $allowedDepth
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    public function getStructureItems(Node $tree, int $allowedDepth): array
    {
        $this->allowedDepth = $allowedDepth;
        $this->getTreeAsArray($tree);
        return $this->structure;
    }

    /**
     * Prepare tree array.
     *
     * @param Node $tree
     * @return void
     * @throws LocalizedException|NoSuchEntityException
     */
    private function getTreeAsArray(Node $tree): void
    {
        $this->structure[] = $this->getTreeItemAsArray($tree);
        if ($this->allowedDepth > $this->depth && $tree->hasChildren()) {
            $this->depth++;
            foreach ($tree->getChildren() as $child) {
                $this->getTreeAsArray($child);
            }
        }
    }

    /**
     * Get tree item as array.
     *
     * @param Node $tree
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    private function getTreeItemAsArray(Node $tree): array
    {
        return [
            'id' => $this->idEncoder->encode((string) $tree->getData(StructureInterface::ENTITY_ID)),
            'parent_id' => $this->idEncoder->encode((string) $tree->getData(StructureInterface::PARENT_ID)),
            'entity' => $this->getEntityData($tree)
        ];
    }

    /**
     * Get tree item entity data
     *
     * @param Node $tree
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    private function getEntityData(Node $tree): array
    {
        if ((int)$tree->getData(StructureInterface::ENTITY_TYPE) === StructureInterface::TYPE_TEAM) {
            return [
                'type' => HierarchyInterface::TYPE_TEAM,
                'id' => $this->idEncoder->encode($tree->getData(TeamInterface::TEAM_ID)),
                'name' => $tree->getData(TeamInterface::NAME),
                'description' => $tree->getData(TeamInterface::DESCRIPTION)
            ];
        }
        $entity = $this->customerData->execute(
            $this->customerRepository->get($tree->getData(CustomerInterface::EMAIL))
        );
        $entity['type'] = HierarchyInterface::TYPE_CUSTOMER;

        return $entity;
    }
}
