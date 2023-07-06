<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Mutation\BatchResult;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuoteGraphQl\Exception\GraphQlNegotiableQuoteInvalidStateException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for closing negotiable quotes
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CloseNegotiableQuotesForUser
{
    /**
     * @var NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var NegotiableQuote
     */
    private $negotiableQuote;

    /**
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param Customer $customer
     * @param Quote $quote
     * @param NegotiableQuote $negotiableQuote
     */
    public function __construct(
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        Customer $customer,
        Quote $quote,
        NegotiableQuote $negotiableQuote
    ) {
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->negotiableQuote = $negotiableQuote;
    }

    /**
     * Close negotiable quote
     *
     * @param string[] $maskedQuoteIds
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(array $maskedQuoteIds, int $customerId, WebsiteInterface $website): array
    {
        $this->customer->validateCanManage($customerId);
        $ownedQuotes = $this->negotiableQuote->getOwnedNegotiableQuotes($maskedQuoteIds, $customerId, $website);
        $closedQuotes = [];
        $operationResults = [];

        foreach ($maskedQuoteIds as $maskedQuoteId) {
            $operationResult = [
                'quote_uid' => $maskedQuoteId
            ];

            try {
                $quote = $this->validateQuoteExists($ownedQuotes, $maskedQuoteId);
                $this->quote->validateCanClose($quote);

                if ($this->negotiableQuoteManagement->close($quote->getId())) {
                    $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
                    $closedQuotes[$maskedQuoteId] = [
                        "uid" => $maskedQuoteId,
                        'status' => $negotiableQuote->getStatus(),
                        'name' => $negotiableQuote->getQuoteName(),
                        'created_at' => $quote->getCreatedAt(),
                        'updated_at' => $quote->getUpdatedAt(),
                        'model' => $quote
                    ];
                } else {
                    throw new LocalizedException(__('Unable to close the negotiable quote.'));
                }
            } catch (GraphQlNoSuchEntityException $e) {
                $operationResult['errors'][] = [
                    'error_type' => 'NoSuchEntityUidError',
                    'message' => $e->getMessage(),
                    'uid' => $maskedQuoteId
                ];
            } catch (GraphQlNegotiableQuoteInvalidStateException $e) {
                $operationResult['errors'][] = [
                    'error_type' => 'NegotiableQuoteInvalidStateError',
                    'message' => $e->getMessage()
                ];
            } catch (\Exception $e) {
                $operationResult['errors'][] = [
                    'error_type' => 'InternalError',
                    'message' => 'Unable to close the negotiable quote.'
                ];
            }

            $operationResults[] = $operationResult;
        }

        $data = [
            'result_status' => $this->determineResultStatus(count($maskedQuoteIds), count($closedQuotes)),
            'operation_results' => $operationResults,
            'closed_quotes' => $closedQuotes
        ];

        return $data;
    }

    /**
     * Validate that an owned quote exists based on the specified maskedQuoteId.
     *
     * Performs the lookup in the provided array to prevent multiple queries to the database.
     *
     * @param CartInterface[] $ownedQuotes
     * @param string $maskedQuoteId
     * @return CartInterface
     * @throws GraphQlNoSuchEntityException
     */
    private function validateQuoteExists(array $ownedQuotes, string $maskedQuoteId): CartInterface
    {
        try {
            $quote = $ownedQuotes[$maskedQuoteId];
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a quote with the specified UID.')
            );
        }

        return $quote;
    }

    /**
     * Determine the status for the batch mutation.
     *
     * When multiple entities are to be mutated, summarize the result of all individual operations.
     *
     * @param int $entityCount
     * @param int $successCount
     * @return string
     */
    private function determineResultStatus(int $entityCount, int $successCount): string
    {
        if ($successCount === 0) {
            return BatchResult::STATUS_FAILURE;
        } elseif ($successCount === $entityCount) {
            return BatchResult::STATUS_SUCCESS;
        } else {
            return BatchResult::STATUS_MIXED;
        }
    }
}
