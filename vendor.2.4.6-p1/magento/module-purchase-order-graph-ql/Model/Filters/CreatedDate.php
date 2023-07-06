<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Filters;

use DateTime;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\PurchaseOrderGraphQl\Model\Resolver\SearchCriteriaFilterInterface;

/**
 * Filter for purchase orders creation date
 */
class CreatedDate implements SearchCriteriaFilterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder, $value): SearchCriteriaBuilder
    {
        $this->validate($value);

        if (isset($value['from'])) {
            $searchCriteriaBuilder->addFilter('created_at', $value['from'], 'gteq');
        }
        if (isset($value['to'])) {
            $searchCriteriaBuilder->addFilter('created_at', $value['to'], 'lteq');
        }
        return $searchCriteriaBuilder;
    }

    /**
     * Validate input data
     *
     * @param array $dates
     * @return void
     * @throws GraphQlInputException
     */
    private function validate(array $dates): void
    {
        if (isset($dates['from'])) {
            $from = $this->getValidFormatDate($dates['from']);
            if (!isset($from)) {
                throw new GraphQlInputException(
                    __('"From" field must be in YYYY-MM-DD HH:MM:SS format.')
                );
            }
        }

        if (isset($dates['to'])) {
            $to = $this->getValidFormatDate($dates['to']);
            if (!isset($to)) {
                throw new GraphQlInputException(
                    __('"To" field must be in YYYY-MM-DD HH:MM:SS format.')
                );
            }
        }

        if (isset($to) && isset($from) && $to < $from) {
            throw new GraphQlInputException(
                __('"To" date must be later than "From" date')
            );
        }
    }

    /**
     * Checks and returns DateTime if passed date is in a valid format, null otherwise
     *
     * Checks if passed date is in a valid format by creating a datetime object from it and then comparing the
     * passed date with the formatted one provided by the created object
     *
     * @param string $date
     * @return DateTime|null
     */
    private function getValidFormatDate(string $date): ?DateTime
    {
        $d = date_create_from_format('Y-m-d H:i:s', $date);
        if ($d && $d->format('Y-m-d H:i:s') === $date) {
            return $d;
        }
        return null;
    }
}
