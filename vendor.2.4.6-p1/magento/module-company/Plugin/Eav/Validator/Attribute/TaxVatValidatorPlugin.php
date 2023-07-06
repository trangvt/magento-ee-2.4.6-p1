<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Plugin\Eav\Validator\Attribute;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Validator\Attribute\Data;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Plugin for checking TaxVat filed for Company custom attributes and remove the validation
 */
class TaxVatValidatorPlugin
{
    /**
     * Validate company taxVat attribute with data models
     *
     * @param Data $subject
     * @param mixed $entity
     */
    public function beforeIsValid(Data $subject, $entity): void
    {
        if ($entity instanceof Customer && $entity->hasData('company_attributes')) {
            $subject->setDeniedAttributesList([CustomerInterface::TAXVAT]);
        }
    }
}
