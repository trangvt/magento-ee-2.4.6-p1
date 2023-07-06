<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Model\Company;

use Magento\Company\Model\Company;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\ReservedAttributeCheckerInterface;

/**
 * Reserved attribute codes list by Company module
 */
class ReservedAttributeList implements ReservedAttributeCheckerInterface
{
    /**
     * @var array
     */
    private $reservedAttributes;

    /**
     * @param Company $companyModel
     * @param array $reservedAttributes
     */
    public function __construct(Company $companyModel, array $reservedAttributes = [])
    {
        $methods = get_class_methods($companyModel);
        foreach ($methods as $method) {
            if (preg_match('/^get([A-Z]{1}.+)/', $method, $matches)) {
                $method = $matches[1];
                $tmp = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $method));
                $reservedAttributes[] = $tmp;
            }
        }
        $this->reservedAttributes = $reservedAttributes;
    }

    /**
     * @inheritdoc
     */
    public function isReservedAttribute(AbstractAttribute $attribute): bool
    {
        return $attribute->getIsUserDefined() && in_array($attribute->getAttributeCode(), $this->reservedAttributes);
    }
}
