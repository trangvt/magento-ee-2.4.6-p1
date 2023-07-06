<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company\Customer;

use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Retrieve company structure data for customer.
 */
class StructureId implements ResolverInterface
{
    private const FIELD_MODEL = 'model';

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var Structure
     */
    private Structure $structure;

    /**
     * @param Uid $uid
     * @param Structure $structure
     */
    public function __construct(Uid $uid, Structure $structure)
    {
        $this->uid = $uid;
        $this->structure = $structure;
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
        if (!isset($value[self::FIELD_MODEL])) {
            throw new LocalizedException(__('"%field" value should be specified', ['field' => self::FIELD_MODEL]));
        }

        /** @var CustomerInterface $customer */
        $customer = $value[self::FIELD_MODEL];

        if (!$customer || !$customer->getId()) {
            throw new GraphQlInputException(__('Could not retrieve customer information.'));
        }

        return $this->uid->encode((string) $this->structure->getStructureByCustomerId($customer->getId())->getId());
    }
}
