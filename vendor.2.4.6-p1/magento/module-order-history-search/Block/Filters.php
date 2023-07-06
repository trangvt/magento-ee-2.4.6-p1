<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Block;

use IntlDateFormatter;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Template;
use Magento\OrderHistorySearch\Model\Config;
use Magento\OrderHistorySearch\Model\Order\Customer\DataProvider as CustomerDataProvider;
use Magento\OrderHistorySearch\Model\Order\Status\DataProvider as OrderStatusDataProvider;

/**
 * Filters block
 *
 * @api
 * @since 100.2.0
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Filters extends Template
{
    /**
     * @var CustomerSessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderStatusDataProvider
     */
    private $statusDataProvider;

    /**
     * @var CustomerDataProvider
     */
    private $customerDataProvider;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * Filters constructor.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param CustomerSessionFactory $customerSessionFactory
     * @param OrderStatusDataProvider $statusDataProvider
     * @param CustomerDataProvider $customerDataProvider
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        CustomerSessionFactory $customerSessionFactory,
        OrderStatusDataProvider $statusDataProvider,
        CustomerDataProvider $customerDataProvider,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->statusDataProvider = $statusDataProvider;
        $this->customerDataProvider = $customerDataProvider;
        $this->timezone = $timezone;
    }

    /**
     * Return search post url
     *
     * @return string
     * @since 100.2.0
     */
    public function getSearchPostUrl(): string
    {
        return $this->getUrl('sales/order/history/');
    }

    /**
     * Get select order status element
     *
     * @return string
     *
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getOrderStatusSelectElementHtml(): string
    {
        return $this
            ->getSelectElementToHtml(
                'order-status',
                'order-status',
                __('Order status'),
                $this->getOrderStatusOptions(),
                'order-statuses'
            );
    }

    /**
     * Gets the select HTML element for the Created By filter.
     *
     * @return string
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getCreatedBySelectElementHtml(): string
    {
        return $this->getSelectElementToHtml(
            'created-by',
            'created-by',
            __('Created By'),
            $this->getCreatedByOptions(),
            ''
        );
    }

    /**
     * Get html select element
     *
     * @param string $name
     * @param string $id
     * @param Phrase $title
     * @param array $options
     * @param string $additionalClasses
     *
     * @return string
     *
     * @throws LocalizedException
     */
    private function getSelectElementToHtml(
        string $name,
        string $id,
        Phrase $title,
        array $options,
        string $additionalClasses = ''
    ): string {
        return $this
            ->getSelectBlock()
            ->setName($name)
            ->setId($id)
            ->setTitle($title)
            ->setValue($this->getRequest()->getParam($id, ''))
            ->setOptions($options)
            ->setClass('multiselect ' . $additionalClasses)
            ->getHtml();
    }

    /**
     * Build date element html string for attribute
     *
     * @param string $name
     * @param string $id
     * @param string $title
     * @param string $additionalAttributes
     * @param string $additionalClasses
     *
     * @return string
     *
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getDateElementToHtml(
        string $name,
        string $id,
        string $title,
        string $additionalAttributes = '',
        $additionalClasses = ''
    ): string {
        $calendar = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Date::class
        )->setId(
            $id
        )->setName(
            $name
        )->setTitle(
            $title
        )->setImage(
            $this->getViewFileUrl('Magento_Theme::calendar.png')
        )->setDateFormat(
            strtolower($this->getLocaleDateFormat())
        )->setExtraParams(
            $additionalAttributes
        )->setClass(
            'input-text ' . $additionalClasses
        )->setValue(
            $this->getRequest()->getParam($id, '')
        )->setMaxDate(
            $this->_localeDate->formatDate()
        );
        return $calendar->getHtml();
    }

    /**
     * Return select block element
     *
     * @return BlockInterface
     *
     * @throws LocalizedException
     */
    private function getSelectBlock(): BlockInterface
    {
        $block = $this->getData('_select_block');
        if (null === $block) {
            $block = $this->getLayout()->createBlock(Select::class);
            $this->setData('_select_block', $block);
        }

        return $block;
    }

    /**
     * Get order statuses as options.
     *
     * @return array
     */
    private function getOrderStatusOptions(): array
    {
        $defaultValue =
            [
                [
                    'value' => '',
                    'label' => __('All'),
                ],
            ];
        return array_merge($defaultValue, $this->statusDataProvider->getOrderStatusOptions());
    }

    /**
     * Get options for the Created By filter.
     *
     * @return array
     * @throws LocalizedException
     */
    private function getCreatedByOptions(): array
    {
        $defaultValue = [
            [
                'value' => '',
                'label' => __('All'),
            ]
        ];

        return array_merge($defaultValue, $this->customerDataProvider->getAllowedCustomerOptions());
    }

    /**
     * Format request input value
     *
     * @param string $name
     *
     * @return string
     * @since 100.2.0
     */
    public function prepareInputValue(string $name): string
    {
        return $this->getRequest()->getParam($name, '');
    }

    /**
     * Format request input value for integer input.
     *
     * @param string $name
     *
     * @return string
     * @since 100.2.0
     */
    public function prepareInputIntegerValue(string $name): string
    {
        $value = $this->getRequest()->getParam($name, '');
        return empty($value) ? '' : (string)(int)floor((float) $value);
    }

    /**
     * Get minimum input length for inputs
     *
     * @return int
     * @since 100.2.0
     */
    public function getMinInputLength(): int
    {
        return $this->config->getMinInputLength();
    }

    /**
     * Get date format for current locale.
     *
     * @return string
     * @since 100.2.0
     */
    public function getLocaleDateFormat(): string
    {
        $format = $this->timezone->getDateFormatWithLongYear();
        $format = preg_replace('/m+/i', 'MM', $format);
        $format = preg_replace('/d+/i', 'DD', $format);
        $format = preg_replace('/Y+/i', 'YYYY', $format);
        return $format;
    }
}
