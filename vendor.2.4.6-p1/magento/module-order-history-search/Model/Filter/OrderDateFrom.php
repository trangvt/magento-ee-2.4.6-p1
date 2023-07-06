<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use DateInterval;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class OrderDateFrom.
 *
 * Model for 'Date From' filter for order search filter.
 */
class OrderDateFrom implements FilterInterface
{
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * OrderDate constructor.
     *
     * @param TimezoneInterface $localeDate
     */
    public function __construct(TimezoneInterface $localeDate)
    {
        $this->localeDate = $localeDate;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        $date = $this->localeDate->date($value);
        $this->localeDate->convertConfigTimeToUtc($date);
        $utcTimestamp = $date->format(DateTime::DATETIME_PHP_FORMAT);

        $ordersCollection->addFieldToFilter(
            OrderInterface::CREATED_AT,
            ['gteq' => $utcTimestamp]
        );

        return $ordersCollection;
    }
}
