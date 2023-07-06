<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Structure;
use Magento\CompanyGraphQl\Model\Company\Users\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer company team data
 */
class Team implements ResolverInterface
{
    /**
     * @var TeamRepositoryInterface
     */
    private $teamRepository;

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
     * @var Structure
     */
    private $structure;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var Customer
     */
    private $customerUser;

    /**
     * @param TeamRepositoryInterface $teamRepository
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param Structure $structure
     * @param CompanyStructure $companyStructure
     * @param Customer $customerUser
     * @param array $allowedResources
     */
    public function __construct(
        TeamRepositoryInterface $teamRepository,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        Structure $structure,
        CompanyStructure $companyStructure,
        Customer $customerUser,
        array $allowedResources = []
    ) {
        $this->teamRepository = $teamRepository;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
        $this->structure = $structure;
        $this->companyStructure = $companyStructure;
        $this->customerUser = $customerUser;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Required parameter "id" is missing'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $company = $value['model'];
        $this->resolverAccess->isAllowed($this->allowedResources);

        try {
            $team = $this->teamRepository->get($this->idEncoder->decode($args['id']));
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $teamStructure = $this->companyStructure->getStructureByTeamId($team->getId());

        if ($teamStructure
            && $parentCustomerStructure = $this->structure->getTeamParentCustomerStructure($teamStructure)
        ) {
            $customer = $this->customerUser->getCustomerById((int)$parentCustomerStructure->getEntityId());
            $userCompanyAttributes = $this->customerUser->getCustomerCompanyAttributes($customer);

            if ($userCompanyAttributes !== null
                && (int)$userCompanyAttributes->getCompanyId() !== (int)$company->getId()
            ) {
                return null;
            }
        } else {
            return null;
        }

        return [
            'id' => $this->idEncoder->encode((string)$team->getId()),
            'name' => $team->getName(),
            'description' => $team->getDescription(),
            'structure_id' => $this->idEncoder->encode(
                (string) $this->companyStructure->getStructureByTeamId($team->getId())->getId()
            )
        ];
    }
}
