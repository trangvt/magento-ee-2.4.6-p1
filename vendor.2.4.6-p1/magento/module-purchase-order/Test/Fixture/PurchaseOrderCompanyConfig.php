<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PurchaseOrder\Model\Company\Config\Repository as PurchaseOrderCompanyConfigRepository;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Purchase order company config fixture
 */
class PurchaseOrderCompanyConfig implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'company_id' => null,
        'is_purchase_order_enabled' => true
    ];

    /**
     * @var PurchaseOrderCompanyConfigRepository
     */
    private PurchaseOrderCompanyConfigRepository $configRepository;

    /**
     * @param PurchaseOrderCompanyConfigRepository $configRepository
     */
    public function __construct(
        PurchaseOrderCompanyConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException(__('"%field" is required', ['field' => 'company_id']));
        }

        $config = $this->configRepository->get($data['company_id']);
        $config->setIsPurchaseOrderEnabled(
            $data['is_purchase_order_enabled'] ?? self::DEFAULT_DATA['is_purchase_order_enabled']
        );
        $this->configRepository->save($config);

        return $config;
    }
}
