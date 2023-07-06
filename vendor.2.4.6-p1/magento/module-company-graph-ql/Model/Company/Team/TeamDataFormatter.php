<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Team;

use Laminas\Validator\LessThan;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Company\Team\Authorization;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Class for validate and format data for further handling
 */
class TeamDataFormatter
{
    /**
     * Max length of team name field
     */
    private const TEAM_NAME_MAX_LENGTH = 40;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param Structure $structure
     * @param Authorization $authorization
     * @param Uid $idEncoder
     */
    public function __construct(
        Structure $structure,
        Authorization $authorization,
        Uid $idEncoder
    ) {
        $this->structure = $structure;
        $this->authorization = $authorization;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Prepare data for team creation
     *
     * @param int $userId
     * @param array $teamInputData
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    public function prepareCreateData(int $userId, array $teamInputData): array
    {
        $sanitizedInputData = [
            'target_id' => $this->getTargetStructureId($userId, $teamInputData),
            'name' => trim($teamInputData['name']),
            'description' => isset($teamInputData['description']) ? trim($teamInputData['description']) : ''
        ];

        if (empty($sanitizedInputData['name'])) {
            throw new GraphQlInputException(
                __('Invalid value of "%1" provided for the name field.', $sanitizedInputData['name'])
            );
        }

        $this->checkMaxLenghtOfName($sanitizedInputData['name']);

        if (!$this->authorization->isAllowedToCreateTeam($userId, $sanitizedInputData['target_id'])) {
            throw new GraphQlAuthorizationException(
                __('You do not have permission to create a team from the specified target ID.')
            );
        }

        return $sanitizedInputData;
    }

    /**
     * Retrieve target structure id based on create team mutation input
     *
     * @param int $userId
     * @param array $teamInputData
     * @return int
     * @throws GraphQlInputException
     */
    private function getTargetStructureId(int $userId, array $teamInputData): int
    {
        if (isset($teamInputData['target_id'])) {
            return (int) $this->idEncoder->decode((string) $teamInputData['target_id']);
        }

        $structure = $this->structure->getStructureByCustomerId($userId);

        if ($structure === null) {
            throw new GraphQlInputException(__('Customer is not a company user.'));
        }

        return (int) $structure->getId();
    }

    /**
     * Prepare data for team update
     *
     * @param int $userId
     * @param array $teamInputData
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    public function prepareUpdateData(int $userId, array $teamInputData): array
    {
        $teamInputData['id'] = (int)$this->idEncoder->decode((string)$teamInputData['id']);

        if (!$this->authorization->isAllowedToUpdateTeam($userId, $teamInputData['id'])) {
            throw new GraphQlAuthorizationException(
                __('You are not authorized to update the team.')
            );
        }
        if (isset($teamInputData['name'])) {
            $teamInputData['name'] = trim($teamInputData['name']);
            if (empty($teamInputData['name'])) {
                throw new GraphQlInputException(
                    __(
                        'Invalid value of "%1" provided for the name field.',
                        $teamInputData['name']
                    )
                );
            }
            $this->checkMaxLenghtOfName($teamInputData['name']);
        }

        if (isset($teamInputData['description'])) {
            $teamInputData['description'] = trim($teamInputData['description']);
        }

        return $teamInputData;
    }

    /**
     * Check max length of name field
     *
     * @param string $name
     * @throws GraphQlInputException
     */
    private function checkMaxLenghtOfName(string $name): void
    {
        $lengthValidator = new LessThan(['max' => self::TEAM_NAME_MAX_LENGTH]);
        if (!$lengthValidator->isValid(strlen($name))) {
            throw new GraphQlInputException(__(
                'Company team name must not be more than %1 characters.',
                self::TEAM_NAME_MAX_LENGTH
            ));
        }
    }
}
