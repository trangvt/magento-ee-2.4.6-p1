<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Plugin\Sales\Block\Order;

use Magento\Framework\Phrase;
use Magento\OrderHistorySearch\Model\OrderFilterBuilderService as FilterService;
use Magento\Sales\Block\Order\History;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Theme\Block\Html\Pager;

/**
 * Class HistoryPlugin.
 *
 * Plugin for orders filter grid.
 */
class HistoryPlugin
{
    /**
     * @var FilterService
     */
    private $filterService;

    /**
     * @var Pager
     */
    private $pager;

    /**
     * HistoryPlugin constructor.
     *
     * @param FilterService $filterService
     * @param Pager $pager
     */
    public function __construct(FilterService $filterService, Pager $pager)
    {
        $this->filterService = $filterService;
        $this->pager = $pager;
    }

    /**
     * Add additional order filters if present
     *
     * @param History $subject
     * @param bool|Collection $result
     *
     * @return bool|Collection
     */
    public function afterGetOrders(History $subject, $result)
    {
        $request = $subject->getRequest();

        if (!$result || null === $request->getParam('advanced-filtering') || $result->hasFlag('advanced-filtering')) {
            return $result;
        }

        $params = array_merge(
            [
                $this->pager->getPageVarName() => $this->pager->getCurrentPage(),
                $this->pager->getLimitVarName() => $this->pager->getLimit(),
            ],
            $request->getParams()
        );

        return $this->filterService->applyOrderFilters($result, $params);
    }

    /**
     * Re-define message for no orders found.
     *
     * @param History $subject
     * @param Phrase $result
     * @return Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetEmptyOrdersMessage(History $subject, $result): Phrase
    {
        return __('Your search did not return any results.');
    }
}
