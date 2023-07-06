<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\PurchaseOrder\Model\Config;

/**
 * Ensure customer is authorized and the field is populated
 */
class ValidateRequest
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[]
     */
    private $availableActions;

    /**
     * @param Config $config
     * @param string[] $availableActions
     */
    public function __construct(
        Config $config,
        array $availableActions = []
    ) {
        $this->config = $config;
        $this->availableActions = $availableActions;
    }

    /**
     * Ensure customer is authorized and the field is populated
     *
     * @param ContextInterface $context
     * @param array|null $args
     * @param string $field
     * @return void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        $context,
        ?array $args,
        string $field
    ): void {
        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!$this->config->isEnabledForCurrentCustomerAndWebsite()) {
            throw new GraphQlAuthorizationException(__('Purchase order functionality is not enabled.'));
        }

        if (!empty($args['action']) && !in_array($args['action'], $this->availableActions)) {
            throw new GraphQlInputException(__('Parameter action is incorrect.'));
        }

        if (empty($args['input'][$field])) {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%field" is missing.',
                    [
                        'field' => $field
                    ]
                )
            );
        }

        if (!is_array($args['input'][$field])) {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%field" must be an array.',
                    [
                        'field' => $field
                    ]
                )
            );
        }
    }
}
