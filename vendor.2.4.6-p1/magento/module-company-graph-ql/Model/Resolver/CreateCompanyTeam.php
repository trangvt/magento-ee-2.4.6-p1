<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Model\Company\Structure;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Team\ExtractTeamData;
use Magento\CompanyGraphQl\Model\Company\Team\TeamDataFormatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Create a new team for company assigned to a user.
 */
class CreateCompanyTeam implements ResolverInterface
{
    private const FIELD_INPUT = 'input';
    private const ALLOWED_RESOURCES = ['Magento_Company::users_edit'];

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var TeamDataFormatter
     */
    private $dataFormatter;

    /**
     * @var TeamInterfaceFactory
     */
    private $teamFactory;

    /**
     * @var TeamRepositoryInterface
     */
    private $teamRepository;

    /**
     * @var ExtractTeamData
     */
    private $extractTeamData;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @param ExtractTeamData $extractTeamData
     * @param CompanyManagementInterface $companyManagement
     * @param Structure $structure
     * @param TeamDataFormatter $dataFormatter
     * @param TeamInterfaceFactory $teamFactory
     * @param TeamRepositoryInterface $teamRepository
     * @param ResolverAccess $resolverAccess
     */
    public function __construct(
        ExtractTeamData $extractTeamData,
        CompanyManagementInterface $companyManagement,
        Structure $structure,
        TeamDataFormatter $dataFormatter,
        TeamInterfaceFactory $teamFactory,
        TeamRepositoryInterface $teamRepository,
        ResolverAccess $resolverAccess
    ) {
        $this->extractTeamData = $extractTeamData;
        $this->companyManagement = $companyManagement;
        $this->structure = $structure;
        $this->dataFormatter = $dataFormatter;
        $this->teamFactory = $teamFactory;
        $this->teamRepository = $teamRepository;
        $this->resolverAccess = $resolverAccess;
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
        if (empty($args[self::FIELD_INPUT]) || !is_array($args[self::FIELD_INPUT])) {
            throw new GraphQlInputException(__('"%field" value should be specified', ['field' => self::FIELD_INPUT]));
        }

        $this->resolverAccess->isAllowed(self::ALLOWED_RESOURCES);
        $preparedData = $this->dataFormatter->prepareCreateData($context->getUserId(), $args['input']);

        try {
            $team = $this->teamFactory->create(['data' => $preparedData]);
            $team->setHasDataChanges(true);
            $this->teamRepository->create(
                $team,
                $this->companyManagement->getByCustomerId($context->getUserId())->getId()
            );
            $this->structure->moveNode(
                $this->structure->getStructureByTeamId($team->getId())->getId(),
                $preparedData['target_id']
            );
        } catch (\Exception $e) {
            throw new LocalizedException(__('Team could not be created'), $e);
        }

        return [
            'team' => $this->extractTeamData->execute($team)
        ];
    }
}
