<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\LocalizedException;
use Magento\NegotiableQuote\Model\Creator;
use Magento\Framework\Exception\IntegrationException;
use Magento\NegotiableQuote\Api\Data\CommentInterface;

/**
 * Negotiable quote comment model
 */
class Comment
{
    /**
     * @var Creator
     */
    private $creator;

    /**
     * @param Creator $creator
     */
    public function __construct(Creator $creator)
    {
        $this->creator = $creator;
    }

    /**
     * Returns negotiable quote creator data
     *
     * @param CommentInterface $comment
     * @param int $quoteId
     * @return array
     * @throws IntegrationException
     * @throws LocalizedException
     */
    public function getCommentCreator(CommentInterface $comment, int $quoteId): array
    {
        $firstName = $lastName = '';
        if ($comment->getCreatorId()) {
            $authorName = $this->creator->retrieveCreatorName(
                $comment->getCreatorType(),
                $comment->getCreatorId(),
                $quoteId
            );
            if ($authorName) {
                $splitName = preg_split("/\s+(?=\S*+$)/", $authorName);
                list($firstName, $lastName) = $splitName;
            }
        }

        $buyerName = [
            'firstname' => $firstName,
            'lastname' => $lastName
        ];

        return $buyerName;
    }
}
