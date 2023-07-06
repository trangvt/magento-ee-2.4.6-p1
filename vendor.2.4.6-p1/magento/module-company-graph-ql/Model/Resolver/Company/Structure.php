<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\Company\Model\Company\Structure as CompanyTreeManagement;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Structure\Validate;
use Magento\CompanyGraphQl\Model\Company\StructureFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer associated company hierarchy
 */
class Structure implements ResolverInterface
{
    /**
     * @var CompanyTreeManagement
     */
    private $treeManagement;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var StructureFactory
     */
    private $structureFactory;

    /**
     * @var Validate
     */
    private $validateStructure;

    /**
     * @param CompanyTreeManagement $treeManagement
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param StructureFactory $structureFactory
     * @param Validate $validateStructure
     * @param array $allowedResources
     */
    public function __construct(
        CompanyTreeManagement $treeManagement,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        StructureFactory $structureFactory,
        Validate $validateStructure,
        array $allowedResources = []
    ) {
        $this->treeManagement = $treeManagement;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
        $this->structureFactory = $structureFactory;
        $this->validateStructure = $validateStructure;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($args['rootId'])) {
            $args['rootId'] = $this->idEncoder->decode($args['rootId']);

            if ($args['rootId'] < 0) {
                throw new GraphQlInputException(__('root_id value must be greater or equal to 0.'));
            }
        }

        if ($args['depth'] < 0) {
            throw new GraphQlInputException(__('depth value must be greater or equal to 0.'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $company = $value['model'];
        $structureItems = null;
        $structure = $this->structureFactory->create();
        $this->resolverAccess->isAllowed($this->allowedResources);
        $customerId = $context->getUserId();

        try {
            $tree = !isset($args['rootId']) ? $this->treeManagement->getTreeByCustomerId($customerId)
                : $this->treeManagement->getTreeById($args['rootId']);

            if ($tree === null || !$this->validateStructure->validateStructureRootId($tree, $company)) {
                $structureItems = null;
            } else {
                $this->treeManagement->addDataToTree($tree);
                $this->treeManagement->filterTree($tree, 'is_active', true);
                $structureItems = $structure->getStructureItems($tree, (int)$args['depth']);
            }
        } catch (\Exception $e) {
            $structureItems = null;
        }

        return [
            'items' => $structureItems
        ];
    }
}
