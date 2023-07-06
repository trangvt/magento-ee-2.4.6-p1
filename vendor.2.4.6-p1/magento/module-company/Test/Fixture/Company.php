<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Fixture;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\DataObject;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new company
 */
class Company implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id' => null,
        'status' => CompanyInterface::STATUS_APPROVED,
        'company_name' => 'Company %uniqid%',
        'legal_name' => null,
        'company_email' => 'company%uniqid%@magento.com',
        'vat_tax_id' => null,
        'reseller_id' => null,
        'comment' => 'Comment',
        'street' => ['123 Street'],
        'city' => 'City',
        'country_id' => 'US',
        'region' => null,
        'region_id' => 1,
        'postcode' => 'Postcode',
        'telephone' => '5555555555',
        'customer_group_id' => 1,
        'sales_representative_id' => null, // required
        'super_user_id' => null, // required
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @param ServiceFactory $serviceFactory
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $processor
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        DataMerger $dataMerger,
        ProcessorInterface $processor
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        return $this->serviceFactory->create(CompanyRepositoryInterface::class, 'save')->execute(
            [
                'company' => $this->processor->process($this, $this->dataMerger->merge(self::DEFAULT_DATA, $data))
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->serviceFactory->create(CompanyRepositoryInterface::class, 'deleteById')->execute(
            [
                'companyId' => $data['entity_id']
            ]
        );
    }
}
