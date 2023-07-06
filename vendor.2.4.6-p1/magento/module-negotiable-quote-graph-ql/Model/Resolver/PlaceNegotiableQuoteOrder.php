<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Helper\Error\AggregateExceptionMessageFormatter;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\PlaceNegotiableQuoteOrder as PlaceNegotiableQuoteOrderModel;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Resolver for placing a negotiable quote order
 */
class PlaceNegotiableQuoteOrder implements ResolverInterface
{
    /**
     * @var PlaceNegotiableQuoteOrderModel
     */
    private $placeNegotiableQuoteOrder;

    /**
     * @var AggregateExceptionMessageFormatter
     */
    private $errorMessageFormatter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param PlaceNegotiableQuoteOrderModel $placeNegotiableQuoteOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param AggregateExceptionMessageFormatter $errorMessageFormatter
     */
    public function __construct(
        PlaceNegotiableQuoteOrderModel $placeNegotiableQuoteOrder,
        OrderRepositoryInterface $orderRepository,
        AggregateExceptionMessageFormatter $errorMessageFormatter
    ) {
        $this->placeNegotiableQuoteOrder = $placeNegotiableQuoteOrder;
        $this->orderRepository = $orderRepository;
        $this->errorMessageFormatter = $errorMessageFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['quote_uid'])) {
            throw new GraphQlInputException(__('Required parameter "quote_uid" is missing'));
        }

        $maskedCartId = $args['input']['quote_uid'];

        try {
            $orderId = $this->placeNegotiableQuoteOrder->execute($context, $maskedCartId);
            $order = $this->orderRepository->get($orderId);
        } catch (LocalizedException $e) {
            throw $this->errorMessageFormatter->getFormatted(
                $e,
                __('Unable to place order: A server error stopped your order from being placed. ' .
                    'Please try to place your order again'),
                'Unable to place order',
                $field,
                $context,
                $info
            );
        }

        return [
            'order' => [
                'order_number' => $order->getIncrementId(),
            ],
        ];
    }
}
