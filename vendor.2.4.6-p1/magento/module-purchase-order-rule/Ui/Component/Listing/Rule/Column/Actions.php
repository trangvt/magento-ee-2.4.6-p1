<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Ui\Component\Listing\Rule\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Company\Api\AuthorizationInterface;

/**
 * Provide actions to the approval rules grid
 */
class Actions extends Column
{
    /**
     * Required resource for view action authorization.
     */
    const RULE_VIEW_RESOURCE = 'Magento_PurchaseOrderRule::view_approval_rules';

    /**
     * Required resource for edit action authorization.
     */
    const RULE_EDIT_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->authorization = $authorization;
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        $config = $this->getData('config');
        $this->setData('config', $config);

        parent::prepare();
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!$this->authorization->isAllowed(self::RULE_VIEW_RESOURCE)
            || !isset($dataSource['data']['items'])
        ) {
            return $dataSource;
        }

        $count = count($dataSource['data']['items']);
        foreach ($dataSource['data']['items'] as &$item) {
            if ($count >= 1 && !$this->authorization->isAllowed(self::RULE_EDIT_RESOURCE)) {
                    $item[$this->getData('name')]['view'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'purchaseorderrule/view',
                            ['rule_id' => $item['rule_id']]
                        ),
                        'label' => __('View'),
                        'hidden' => false,
                        'post' => false,
                    ];
            } elseif ($count >= 1 && $this->authorization->isAllowed(self::RULE_EDIT_RESOURCE)) {
                $item[$this->getData('name')]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'purchaseorderrule/edit',
                        ['rule_id' => $item['rule_id']]
                    ),
                    'label' => __('Edit'),
                    'hidden' => false,
                    'post' => false,
                ];
                $item[$this->getData('name')]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'purchaseorderrule/delete',
                        ['id' => $item['rule_id']]
                    ),
                    'label' => __('Delete'),
                    'hidden' => false,
                    'confirm' => [
                        'title' => __('Delete Rule'),
                        'message' => __('Are you sure you want to delete this rule?')
                    ],
                    'post' => true,
                ];
            }
        }

        return $dataSource;
    }
}
