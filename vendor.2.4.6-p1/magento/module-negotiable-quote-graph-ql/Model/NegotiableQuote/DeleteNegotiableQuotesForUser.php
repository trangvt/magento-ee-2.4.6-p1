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
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGridInterface;
use Magento\NegotiableQuoteGraphQl\Exception\GraphQlNegotiableQuoteInvalidStateException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for deleting negotiable quotes
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteNegotiableQuotesForUser
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var QuoteGridInterface
     */
    private $quoteGrid;

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
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param QuoteGridInterface $quoteGrid
     * @param Customer $customer
     * @param Quote $quote
     * @param NegotiableQuote $negotiableQuote
     */
    public function __construct(
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        QuoteGridInterface $quoteGrid,
        Customer $customer,
        Quote $quote,
        NegotiableQuote $negotiableQuote
    ) {
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->quoteGrid = $quoteGrid;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->negotiableQuote = $negotiableQuote;
    }

    /**
     * Deletes negotiable quote
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
        $deletedQuoteCount = 0;
        $operationResults = [];

        foreach ($maskedQuoteIds as $maskedQuoteId) {
            $operationResult = [
                'quote_uid' => $maskedQuoteId
            ];

            try {
                $quote = $this->validateQuoteExists($ownedQuotes, $maskedQuoteId);
                $this->quote->validateCanDelete($quote);

                $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
                $this->negotiableQuoteRepository->delete($negotiableQuote);
                $this->quoteGrid->remove($quote);
                $deletedQuoteCount++;
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
                    'message' => 'Unable to delete the negotiable quote.'
                ];
            }

            $operationResults[] = $operationResult;
        }

        $data = [
            'result_status' => $this->determineResultStatus(count($maskedQuoteIds), $deletedQuoteCount),
            'operation_results' => $operationResults
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
