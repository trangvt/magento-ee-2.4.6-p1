<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\Collection\QuoteIdMaskCollection;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\Collection\QuoteIdMaskCollectionFactory;
use Magento\Quote\Model\QuoteIdMask as QuoteIdMaskModel;

class QuoteIdMask
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Random
     */
    private $randomDataGenerator;

    /**
     * @var array
     */
    private $maskedQuoteIdMapping = [];

    /**
     * @var QuoteIdMaskCollectionFactory
     */
    private $maskedIdCollectionFactory;

    /**
     * @param QuoteIdMaskCollectionFactory $maskedIdCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Random $randomDataGenerator
     */
    public function __construct(
        QuoteIdMaskCollectionFactory $maskedIdCollectionFactory,
        ResourceConnection $resourceConnection,
        Random $randomDataGenerator
    ) {
        $this->maskedIdCollectionFactory = $maskedIdCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->randomDataGenerator = $randomDataGenerator;
    }

    /**
     * Get the quote id mapped to the specified masked quote id.
     *
     * Typically used when processing a masked quote id provided by the user.
     *
     * @param string $maskedQuoteId
     * @return int
     * @throws GraphQlNoSuchEntityException
     */
    public function getUnmaskedQuoteId(string $maskedQuoteId): int
    {
        return $this->getUnmaskedQuoteIds([$maskedQuoteId])[$maskedQuoteId];
    }

    /**
     * Get the masked quote id mapped to the specified quote id.
     *
     * Typically used when processing a previously unmasked quote id for the output.
     *
     * @param int $quoteId
     * @return string
     * @throws LocalizedException
     */
    public function getMaskedQuoteId(int $quoteId): string
    {
        return $this->getMaskedQuoteIds([$quoteId])[$quoteId];
    }

    /**
     * Get the quote id mappings for an array of masked quote ids.
     *
     * Typically used when processing masked quote ids provided by the user.
     *
     * @param string[] $maskedQuoteIds
     * @param bool $ignoreErrors
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function getUnmaskedQuoteIds(array $maskedQuoteIds, bool $ignoreErrors = false): array
    {
        $unmaskedIds = [];
        $unmappedMaskedIds = [];

        foreach ($maskedQuoteIds as $maskedQuoteId) {
            if (isset($this->maskedQuoteIdMapping[$maskedQuoteId])) {
                $unmaskedIds[$maskedQuoteId] = $this->maskedQuoteIdMapping[$maskedQuoteId];
            } else {
                $unmappedMaskedIds[] = $maskedQuoteId;
            }
        }

        if ($unmappedMaskedIds) {
            /** @var QuoteIdMaskCollection $maskCollection */
            $maskCollection = $this->maskedIdCollectionFactory->create();
            $maskCollection->addFieldToFilter('masked_id', ['in' => $unmappedMaskedIds]);
            /** @var QuoteIdMaskModel $mask */
            foreach ($maskCollection->getItems() as $mask) {
                $unmaskedIds[$mask->getMaskedId()] = (int)$mask->getQuoteId();
                $this->maskedQuoteIdMapping[$mask->getMaskedId()] = (int)$mask->getQuoteId();
            }

            $unmappedMaskedIds = array_diff($maskedQuoteIds, array_keys($unmaskedIds));
        }

        if (!$ignoreErrors && count($unmappedMaskedIds) > 0) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find quotes with the following UIDs: ' . implode(', ', $unmappedMaskedIds))
            );
        }

        return $unmaskedIds;
    }

    /**
     * Get the masked quote id mappings for an array of quote ids.
     *
     * Typically used when processing previously unmasked quote ids for the output.
     *
     * @param int[] $quoteIds
     * @return array
     * @throws LocalizedException
     */
    public function getMaskedQuoteIds(array $quoteIds): array
    {
        $idsToMasks = array_flip($this->maskedQuoteIdMapping);
        $maskedIds = [];
        $unmappedIds = [];

        foreach ($quoteIds as $quoteId) {
            if (isset($idsToMasks[$quoteId])) {
                $maskedIds[$quoteId] = $idsToMasks[$quoteId];
            } else {
                $unmappedIds[] = $quoteId;
            }
        }

        if ($unmappedIds) {
            /** @var QuoteIdMaskCollection $maskCollection */
            $maskCollection = $this->maskedIdCollectionFactory->create();
            $maskCollection->addFieldToFilter('quote_id', ['in' => $unmappedIds]);

            /** @var QuoteIdMaskModel $mask */
            foreach ($maskCollection->getItems() as $mask) {
                $maskedIds[(int)$mask->getQuoteId()] = $mask->getMaskedId();
                $this->maskedQuoteIdMapping[$mask->getMaskedId()] = (int)$mask->getQuoteId();
            }

            $unmappedIds = array_diff($quoteIds, array_keys($maskedIds));
            if ($unmappedIds) {
                $newMaskedIds = $this->createQuoteIdMasks($unmappedIds);
                foreach ($newMaskedIds as $id => $maskedId) {
                    $maskedIds[$id] = $maskedId;
                }
            }
        }

        return $maskedIds;
    }

    /**
     * Creates entries in the quote_id_mask table for each given unmasked quote id
     *
     * @param int[] $quoteIds
     * @return array
     * @throws LocalizedException
     */
    private function createQuoteIdMasks(array $quoteIds): array
    {
        if (empty($quoteIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('quote_id_mask');
        $data = [];
        $idsToMasks = [];
        foreach ($quoteIds as $quoteId) {
            $maskedId = $this->randomDataGenerator->getUniqueHash();
            $idsToMasks[$quoteId] = $maskedId;
            $data[] = ['quote_id' => $quoteId, 'masked_id' => $maskedId];
        }

        try {
            $insertedRows = $connection->insertMultiple($table, $data);
        } catch (Exception $e) {
            throw new LocalizedException(__("Failed to create quote id masks."));
        }
        if ($insertedRows !== count($quoteIds)) {
            throw new LocalizedException(__("Failed to create quote id masks."));
        }

        $this->maskedQuoteIdMapping = array_merge($this->maskedQuoteIdMapping, array_flip($idsToMasks));

        return $idsToMasks;
    }
}
