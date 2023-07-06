<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Model\Company\Team\Authorization;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolve the deleteCompanyTeam mutation
 */
class DeleteCompanyTeam implements ResolverInterface
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
     * @var Authorization
     */
    private $authorization;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param TeamRepositoryInterface $teamRepository
     * @param Authorization $authorization
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     */
    public function __construct(
        TeamRepositoryInterface $teamRepository,
        Authorization $authorization,
        ResolverAccess $resolverAccess,
        Uid $idEncoder
    ) {
        $this->teamRepository = $teamRepository;
        $this->authorization = $authorization;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed(self::ALLOWED_RESOURCES);

        $teamId = (int)$this->idEncoder->decode($args['id']);
        if (!$this->authorization->isAllowedToDeleteTeam((int)$context->getUserId(), $teamId)) {
            throw new GraphQlAuthorizationException(
                __('You are not authorized to delete the team.')
            );
        }

        try {
            $team = $this->teamRepository->get($teamId);
            $this->teamRepository->delete($team);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Can not delete team with id "%1"', $args['id']),
                $e
            );
        }

        return [
            'success' => true
        ];
    }
}
