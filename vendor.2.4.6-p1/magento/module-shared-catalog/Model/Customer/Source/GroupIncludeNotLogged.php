<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\Customer\Source;

/**
 * Class GroupIncludeNotLogged.
 */
class GroupIncludeNotLogged extends Group
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Customer Groups'), 'value' => $this->getCustomerGroups(false, false)],
            ['label' => __('Shared Catalogs'), 'value' => $this->getCustomerGroups(true)],
        ];
    }
}
