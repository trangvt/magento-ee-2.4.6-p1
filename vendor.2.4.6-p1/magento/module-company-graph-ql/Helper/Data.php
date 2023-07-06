<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Helper;

use Magento\CompanyGraphQl\Model\Company\Users\Formatter;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Helper for company resolvers
 */
class Data
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Sets not required company attributes
     *
     * @param array $customerData
     * @param array $attributeKeys
     * @param CustomerInterface|null $customer
     * @return array
     */
    public function setCompanyAttributes(
        array $customerData,
        array $attributeKeys,
        CustomerInterface $customer = null
    ): array {
        foreach ($attributeKeys as $key) {
            if (isset($customerData[$key])) {
                if ($key ==='status') {
                    $customerData['status'] = $this->formatter->formatStatusFromEnum($customerData['status']);
                }
                $customerData['extension_attributes']['company_attributes'][$key] = $customerData[$key];
            } elseif ($customer!==null) {
                $customerData['extension_attributes']['company_attributes'][$key] =
                    $customer->getExtensionAttributes()->getCompanyAttributes()->getData($key);
            }
        }

        return $customerData;
    }
}
