<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Validator\EmailAddress as EmailAddressValidator;

/**
 * Validate input fields
 */
class Validator
{
    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @param EmailAddressValidator $emailAddressValidator
     * @param Structure $structureManager
     * @param CompanyContext $companyContext
     */
    public function __construct(
        EmailAddressValidator $emailAddressValidator,
        Structure $structureManager,
        CompanyContext $companyContext
    ) {
        $this->emailAddressValidator = $emailAddressValidator;
        $this->structureManager = $structureManager;
        $this->companyContext = $companyContext;
    }

    /**
     * Validate user creating fields
     *
     * @param array $userData
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function validateUserCreating(array $userData)
    {
        $this->validateRequiredFields($userData);
        $this->validateEmail($userData['email']);

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());

        if (isset($userData['target_id']) && !in_array($userData['target_id'], $allowedIds['structures'])) {
            throw new GraphQlInputException(__('You are not allowed to do this.'));
        }

        if (!isset($userData['target_id'])) {
            $structure = $this->structureManager->getStructureByCustomerId($this->companyContext->getCustomerId());
            if ($structure === null) {
                throw new LocalizedException(__('Cannot create the customer.'));
            }
        }
    }

    /**
     * Validate user updating fields
     *
     * @param array $userData
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function validateUserUpdating(array $userData)
    {
        if (!$userData['id']) {
            throw new GraphQlInputException(__('Field "id" is not specified'));
        }

        if (isset($userData['email'])) {
            $this->validateEmail($userData['email']);
        }

        $this->validateCustomerId((int)$userData['id']);
    }

    /**
     * Validate required fields
     *
     * @param array $userData
     * @throws GraphQlInputException
     */
    private function validateRequiredFields(array $userData)
    {
        $requiredFields = [
            'job_title',
            'role_id',
            'firstname',
            'lastname',
            'email',
            'telephone',
            'status'
        ];

        $errorInput = [];

        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || !$userData[$field]) {
                $errorInput[] = $field;
            }
        }

        if ($errorInput) {
            throw new GraphQlInputException(
                __('Required parameters are missing: %1.', [implode(', ', $errorInput)])
            );
        }
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @throws GraphQlInputException
     */
    private function validateEmail(string $email)
    {
        if (!$this->emailAddressValidator->isValid($email)) {
            throw new GraphQlInputException(
                __('"%1" is not a valid email address.', $email)
            );
        }
    }

    /**
     * Validate customer id
     *
     * @param int $customerId
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    private function validateCustomerId(int $customerId)
    {
        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        if (!in_array($customerId, $allowedIds['users'])) {
            throw new GraphQlInputException(__('You do not have authorization to perform this action.'));
        }
    }
}
