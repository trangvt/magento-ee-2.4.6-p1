<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\IdEncoder;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Comment;

/**
 * Resolver for the comments associated with a negotiable quote
 */
class QuoteComments implements ResolverInterface
{
    /**
     * @var CommentManagementInterface
     */
    private $commentManagement;

    /**
     * @var Comment
     */
    private $quoteComment;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param CommentManagementInterface $commentManagement
     * @param Comment $quoteComment
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        CommentManagementInterface $commentManagement,
        Comment $quoteComment,
        IdEncoder $idEncoder
    ) {
        $this->commentManagement = $commentManagement;
        $this->quoteComment = $quoteComment;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Get negotiable quote comments
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     *
     * @throws IntegrationException
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
        $comments = $this->commentManagement->getQuoteComments($quoteId);
        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'uid' => $this->idEncoder->encode((string)$comment->getId()),
                'created_at' => $comment->getCreatedAt(),
                'author' => $this->quoteComment->getCommentCreator($comment, $quoteId),
                'text' => $comment->getComment(),
                'creator_type' => $comment->getCreatorType()
            ];
        }

        return $data;
    }
}
