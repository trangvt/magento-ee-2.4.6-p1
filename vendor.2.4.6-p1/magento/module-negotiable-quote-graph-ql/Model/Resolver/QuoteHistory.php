<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\NegotiableQuote\Block\Quote\History;
use Magento\NegotiableQuote\Model\Expiration;
use Magento\NegotiableQuote\Model\History\LogCommentsInformation;
use Magento\NegotiableQuote\Model\History\LogInformation;
use Magento\NegotiableQuote\Model\History\LogProductInformation;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\IdEncoder;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Status;

/**
 * Resolver for the history log entries associated with a negotiable quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteHistory implements ResolverInterface
{
    /**
     * @var HistoryManagementInterface
     */
    private $historyManagement;

    /**
     * @var LogCommentsInformation
     */
    private $historyLogCommentsInformation;

    /**
     * @var LogProductInformation
     */
    private $historyLogProductInformation;

    /**
     * @var LogInformation
     */
    private $historyLogInformation;

    /**
     * @var History
     */
    private $history;

    /**
     * @var Status
     */
    private $statusList;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param HistoryManagementInterface $historyManagement
     * @param LogCommentsInformation $historyLogCommentsInformation
     * @param LogInformation $historyLogInformation
     * @param LogProductInformation $historyLogProductInformation
     * @param History $history
     * @param Status $statusList
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        HistoryManagementInterface $historyManagement,
        LogCommentsInformation $historyLogCommentsInformation,
        LogInformation $historyLogInformation,
        LogProductInformation $historyLogProductInformation,
        History $history,
        Status $statusList,
        IdEncoder $idEncoder
    ) {
        $this->historyManagement = $historyManagement;
        $this->historyLogCommentsInformation = $historyLogCommentsInformation;
        $this->historyLogInformation = $historyLogInformation;
        $this->historyLogProductInformation = $historyLogProductInformation;
        $this->history = $history;
        $this->statusList = $statusList;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Fetches the negotiable quote History log.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value must be specified.'));
        }
        $quote = $value['model'];
        $quoteId = (int)$quote->getId();
        $currency = $quote->getQuoteCurrencyCode();
        $history = $this->historyManagement->getQuoteHistory($quoteId);
        $data = [];
        foreach ($history as $historyLog) {
            if ($historyLog->getIsDraft()) {
                continue;
            }
            $historyId = (int)$historyLog->getId();
            $updates = $this->getUpdates($historyId);

            $comments = null;
            if ($updates->hasComment()) {
                $comments['comment'] = $this->getCommentText((int)$updates->getComment());
            }

            $statuses = $this->getQuoteStatuses($updates);
            $total = $this->getQuoteSubtotal($updates, $currency);
            $expiration = $this->getQuoteExpiration($updates);
            $customLog = $this->getQuoteCustomLog($updates);

            $removedSku = [];
            if ($updates->hasRemovedFromCatalog()) {
                $productsRemoved = $updates->getRemovedFromCatalog();
                foreach ($productsRemoved as $product) {
                    $removedSku[] = $product['sku'];
                }
            }
            $removedFromQuote = [];
            if ($updates->hasRemovedFromCart()) {
                $productsRemovedFromCart = $updates->getRemovedFromCart();
                foreach ($productsRemovedFromCart as $product) {
                    $removedFromQuote[] = $product['product_id'];
                }
            }

            $data[] = [
                'uid' => $this->idEncoder->encode((string)$historyId),
                'created_at' => $historyLog->getCreatedAt(),
                'author' => $this->getLogAuthor($historyLog, $quoteId),
                'change_type' => $historyLog->getStatus(),
                'changes' => [
                    'statuses' => $statuses,
                    'custom_changes' => $customLog,
                    'comment_added' => $comments,
                    'total' => $total,
                    'expiration' => $expiration,
                    'products_removed' => [
                        'products_removed_from_catalog' => $removedSku,
                        'products_removed_from_quote' => $removedFromQuote
                    ]
                ]
            ];
        }
        return $data;
    }

    /**
     * Return object with quote updates.
     *
     * @param int $logId
     * @return DataObject
     */
    private function getUpdates(int $logId): DataObject
    {
        return $this->historyLogInformation->getQuoteUpdates($logId);
    }

    /**
     * Get quote history log statuses
     *
     * @param DataObject $updates
     * @return array|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getQuoteStatuses(DataObject $updates): ?array
    {
        $oldStatus = $newStatus = null;
        $statusChanges = [];
        if ($updates->hasStatus()) {
            $status = $updates->getStatus();
            if (isset($status['new_value']) && isset($status['old_value'])) {
                $statuses = $this->history->checkMultiStatus($status['old_value'], $status['new_value']);
            }
            if (!isset($statuses) || empty($statuses)) {
                $statuses[] = $status;
            }

            $labels = $this->statusList->getStatusLabels();
            foreach ($statuses as $statusLine) {
                if (count($statuses) > 1) {
                    $statusChanges[] = [
                        'old_status' => $labels[$statusLine['old_value']],
                        'new_status' => $labels[$statusLine['new_value']]
                    ];
                } else {
                    if (isset($statusLine['old_value'])) {
                        $oldStatus = $labels[$statusLine['old_value']] ?: $statusLine['old_value'];
                    }
                    if (isset($statusLine['new_value'])) {
                        $newStatus = $labels[$statusLine['new_value']] ?: $statusLine['new_value'];
                    }

                    $statusChanges[] = [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus
                    ];
                }
            }
        }
        $statuses['changes'] = $statusChanges;
        return $statuses;
    }

    /**
     * Return array with quote history log total
     *
     * @param DataObject $updates
     * @param string $currency
     * @return array
     */
    private function getQuoteSubtotal(DataObject $updates, string $currency): array
    {
        $total = [];
        if ($updates->hasSubtotal()) {
            $subtotal = $updates->getSubtotal();
            if (isset($subtotal['old_value'])) {
                $oldPrice = $subtotal['old_value'];
                $total['old_price'] = ['value' => $oldPrice, 'currency' => $currency];
            }
            if (isset($subtotal['new_value'])) {
                $newPrice = $subtotal['new_value'];
                $total['new_price'] = ['value' => $newPrice, 'currency' => $currency];
            }
        }

        return $total;
    }

    /**
     * Get quote history log custom log
     *
     * @param DataObject $updates
     * @return array
     */
    private function getQuoteCustomLog(DataObject $updates): array
    {
        $fieldTitle = $oldValue = $newValue = '';
        if ($updates->hasCustomLog()) {
            $customLog = $updates->getCustomLog();
            if (is_array($customLog)) {
                $this->getCustomLogInfo($customLog, $fieldTitle, $newValue, $oldValue);
            }
        }

        $customLog = [
            'title' => $fieldTitle,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ];
        return $customLog;
    }

    /**
     * Get quote history log expiration date
     *
     * @param DataObject $updates
     * @return array
     */
    public function getQuoteExpiration(DataObject $updates): array
    {
        $expiration = [];
        $oldExpiration = null;
        $newExpiration = null;
        if ($updates->hasExpirationDate()) {
            $expirationDate = $updates->getExpirationDate();
            if (isset($expirationDate['old_value'])) {
                $oldExpiration = ($expirationDate['old_value'] != Expiration::DATE_QUOTE_NEVER_EXPIRES)
                    ? $expirationDate['old_value']
                    : null;
            }
            if (isset($expirationDate['new_value'])) {
                $newExpiration = ($expirationDate['new_value'] != Expiration::DATE_QUOTE_NEVER_EXPIRES)
                    ? $expirationDate['new_value']
                    : 'Never';
            }
        }
        $expiration['old_expiration'] = $oldExpiration;
        $expiration['new_expiration'] = $newExpiration;

        return $expiration;
    }

    /**
     * Get author name of the history log
     *
     * @param HistoryInterface $historyLog
     * @param int $quoteId
     * @return array
     */
    private function getLogAuthor(HistoryInterface $historyLog, int $quoteId): array
    {
        $firstName = $lastName = '';
        $authorName = $this->historyLogCommentsInformation->getLogAuthor($historyLog, $quoteId);

        if (!$authorName instanceof Phrase) {
            $splitName = preg_split("/\s+(?=\S*+$)/", $authorName);
            list($firstName, $lastName) = $splitName;
        }
        return [
            'firstname' => $firstName,
            'lastname' => $lastName
        ];
    }

    /**
     * Prepare history log comment text
     *
     * @param int $commentId
     * @return string
     */
    private function getCommentText(int $commentId): string
    {
        return $this->historyLogCommentsInformation->getCommentText($commentId);
    }

    /**
     * Get custom log info
     *
     * @param array $customLog
     * @param string $fieldTitle
     * @param string $newValue
     * @param string $oldValue
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getCustomLogInfo(array $customLog, string &$fieldTitle, string &$newValue, string &$oldValue): void
    {
        foreach ($customLog as $customLogRecord) {
            $fieldTitle = '';
            if (isset($customLogRecord['product_id']) && $customLogRecord['product_id']) {
                $fieldTitle = $this->historyLogProductInformation->getProductNameById(
                    $customLogRecord['product_id']
                );
            } elseif (isset($customLogRecord['product_sku']) && $customLogRecord['product_sku']) {
                $fieldTitle = $this->historyLogProductInformation->getProductName(
                    $customLogRecord['product_sku']
                );
            }

            if (isset($customLogRecord['field_title']) || $fieldTitle != '') {
                if ($fieldTitle == '' && isset($customLogRecord['field_title'])) {
                    $fieldTitle = $customLogRecord['field_title'];
                }
            }

            $oldValue = $newValue = '';
            if (isset($customLogRecord['values']) && !empty($customLogRecord['values'])) {
                foreach ($customLogRecord['values'] as $customLogValue) {
                    if (isset($customLogValue['old_value'])) {
                        $oldValue = $customLogValue['old_value'];
                    }
                    if (isset($customLogValue['new_value'])) {
                        $newValue = $customLogValue['new_value'];
                    }
                }
            }
        }
    }
}
