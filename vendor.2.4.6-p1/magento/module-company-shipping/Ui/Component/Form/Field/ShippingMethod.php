<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Ui\Component\Form\Field;

use Magento\Ui\Component\Form\Field;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\CompanyShipping\Model\Config as CompanyShippingConfig;
use Magento\Framework\View\Element\UiComponentInterface;

/**
 * UI Component Form Field Class for shipping methods configuration on admin company form
 */
class ShippingMethod extends Field
{
    /**
     * @var CompanyShippingConfig
     */
    private $companyShippingConfig;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CompanyShippingConfig $companyShippingConfig
     * @param array|UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CompanyShippingConfig $companyShippingConfig,
        array $components,
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->companyShippingConfig = $companyShippingConfig;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        $this->setData(
            'config',
            array_replace_recursive(
                (array) $this->getData('config'),
                [
                    'b2bShippingMethods' => $this->companyShippingConfig->getAvailableShippingMethods(),
                ]
            )
        );

        parent::prepare();
    }
}
