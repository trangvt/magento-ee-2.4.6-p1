<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Ui\Component\Listing\Column\Store\Structure;

/**
 * Store options for structure component.
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Store\Ui\Component\Listing\Column\Store\Options
     */
    private $options;

    /**
     * @param \Magento\Store\Ui\Component\Listing\Column\Store\Options $options
     */
    public function __construct(\Magento\Store\Ui\Component\Listing\Column\Store\Options $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->options->toOptionArray();
    }
}
