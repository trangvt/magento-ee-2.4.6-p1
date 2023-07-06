<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Company\Source;

use Magento\Company\Model\Company\Source\Provider\CustomerAttributeOptions;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * List of genders company customer can have.
 */
class Gender implements OptionSourceInterface
{
    /**
     * @var CustomerAttributeOptions
     */
    private $provider;

    /**
     * @param CustomerAttributeOptions $provider
     */
    public function __construct(CustomerAttributeOptions $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return $this->provider->loadOptions('gender');
    }
}
