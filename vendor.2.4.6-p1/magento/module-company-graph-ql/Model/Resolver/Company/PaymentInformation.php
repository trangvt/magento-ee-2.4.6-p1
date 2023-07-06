<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company payment data resolver, used for GraphQL request processing.
 */
class PaymentInformation implements ResolverInterface
{
    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (isset($value['isNewCompany']) && $value['isNewCompany'] === true) {
            return null;
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $value['model'];
        $availablePaymentMethods = [];
        if ($company->getExtensionAttributes() !== null
            && $company->getExtensionAttributes()->getAvailablePaymentMethods()
        ) {
            $availablePaymentMethods = explode(
                ',',
                $company->getExtensionAttributes()->getAvailablePaymentMethods()
            );
        }

        return $availablePaymentMethods;
    }
}
