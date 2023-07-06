<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Data\ProcessorInterface as FixtureProcessor;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Add a comment to the purchase order
 */
class PurchaseOrderComment implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'entity_id' => null,
        'purchase_order_id' => null,
        'creator_id' => null,
        'comment' => 'Comment text %uniqid%',
        'created_at' => null
    ];

    /**
     * @var CommentManagement
     */
    private CommentManagement $commentManagement;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var FixtureProcessor
     */
    private FixtureProcessor $processor;

    /**
     * @param CommentManagement $commentManagement
     * @param DataMerger $dataMerger
     * @param FixtureProcessor $processor
     */
    public function __construct(
        CommentManagement $commentManagement,
        DataMerger $dataMerger,
        FixtureProcessor $processor,
    ) {
        $this->commentManagement = $commentManagement;
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = $this->processor->process(
            $this,
            $this->dataMerger->merge(self::DEFAULT_DATA, $data)
        );

        $this->commentManagement->addComment(
            $data['purchase_order_id'],
            $data['creator_id'],
            $data['comment'],
        );
        $comments = $this->commentManagement->getPurchaseOrderComments($data['purchase_order_id']);
        return $comments->getLastItem();
    }
}
