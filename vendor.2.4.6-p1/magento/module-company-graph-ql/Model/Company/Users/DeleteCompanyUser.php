<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

/**
 * Execute company user deleting
 */
class DeleteCompanyUser
{
    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CompanyContext $companyContext
     * @param Structure $structureManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CompanyContext $companyContext,
        Structure $structureManager,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->structureManager = $structureManager;
        $this->companyContext = $companyContext;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Delete company user
     *
     * @param int $customerId
     * @return bool
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function execute(int $customerId): bool
    {
        if ($customerId == $this->companyContext->getCustomerId()) {
            throw new GraphQlInputException(__('You cannot delete yourself.'));
        }

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        if (!in_array($customerId, $allowedIds['users'])) {
            throw new GraphQlInputException(__('You do not have authorization to perform this action.'));
        }

        try {
            $structure = $this->structureManager->getStructureByCustomerId($customerId);
            if ($structure === null) {
                throw new GraphQlInputException(__('Cannot delete this user.'));
            }
            $customer = $this->customerRepository->getById($customerId);
            /** @var CompanyCustomerInterface $companyAttributes */
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
            $this->customerRepository->save($customer);
            return true;
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new GraphQlInputException(__('Something went wrong.'));
        }
    }
}
