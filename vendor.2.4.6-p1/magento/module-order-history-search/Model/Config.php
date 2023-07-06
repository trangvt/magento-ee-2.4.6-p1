<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config.
 *
 * Configuration model for order history search.
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private const XML_PATH_MIN_INPUT_LENGTH = 'order_history_search/general/min_input_length';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get minimum input length config
     *
     * @return int
     */
    public function getMinInputLength(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_MIN_INPUT_LENGTH);
    }
}
