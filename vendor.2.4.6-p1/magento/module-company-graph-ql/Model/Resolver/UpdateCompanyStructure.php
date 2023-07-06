<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Update teams and user position in the company's hierarchy tree
 */
class UpdateCompanyStructure implements ResolverInterface
{
    /**
     * @var CompanyStructure
     */
    private $companyStructure;

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
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @param CompanyStructure $companyStructure
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param CompanyManagementInterface $companyManagement
     * @param array $allowedResources
     */
    public function __construct(
        CompanyStructure $companyStructure,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        CompanyManagementInterface $companyManagement,
        array $allowedResources = []
    ) {
        $this->companyStructure = $companyStructure;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
        $this->companyManagement = $companyManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthenticationException(
                __('The current customer isn\'t authorized.')
            );
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $movableNodeStructureId = (int)$this->idEncoder->decode($args['input']['tree_id']);
        $destinationNodeStructureId = (int)$this->idEncoder->decode($args['input']['parent_tree_id']);

        try {
            $company = $this->companyManagement->getByCustomerId($context->getUserId());
            $this->companyStructure->moveNode($movableNodeStructureId, $destinationNodeStructureId);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('Failed to update company structure.'));
        }

        return [
            'company' => [
                'model' => $company
            ]
        ];
    }
}
