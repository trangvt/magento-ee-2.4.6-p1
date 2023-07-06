<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\TeamRepositoryInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Team\ExtractTeamData;
use Magento\CompanyGraphQl\Model\Company\Team\TeamDataFormatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Update a team by ID.
 */
class UpdateCompanyTeam implements ResolverInterface
{
    /**
     * Array of allowed resources
     */
    private const ALLOWED_RESOURCES = ['Magento_Company::users_edit'];

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
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var TeamDataFormatter
     */
    private $teamDataFormatter;

    /**
     * @param TeamRepositoryInterface $teamRepository
     * @param ExtractTeamData $extractTeamData
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param TeamDataFormatter $teamDataFormatter
     */
    public function __construct(
        TeamRepositoryInterface $teamRepository,
        ExtractTeamData $extractTeamData,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        TeamDataFormatter $teamDataFormatter
    ) {
        $this->teamRepository = $teamRepository;
        $this->extractTeamData = $extractTeamData;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
        $this->teamDataFormatter = $teamDataFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed(self::ALLOWED_RESOURCES);

        $teamData = $this->teamDataFormatter->prepareUpdateData($context->getUserId(), $args['input']);
        try {
            $team = $this->teamRepository->get($teamData['id']);

            if (isset($teamData['name'])) {
                $team->setName($teamData['name']);
            }

            if (isset($teamData['description'])) {
                $team->setDescription($teamData['description']);
            }

            $this->teamRepository->save($team);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Company could not be updated'),
                $e
            );
        }

        return [
            'team' => $this->extractTeamData->execute($team)
        ];
    }
}
