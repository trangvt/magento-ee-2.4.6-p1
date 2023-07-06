<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote;
use Magento\NegotiableQuote\Controller\FileProcessor;
use Magento\NegotiableQuote\Model\CommentManagement;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterfaceFactory;
use Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment as CommentAttachmentResource;

/**
 * Controller for save draft quote.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Quote implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_NegotiableQuote::save_as_draft';

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var CommentAttachmentResource
     */
    private $commentAttachmentResource;

    /**
     * @var CommentAttachmentInterfaceFactory
     */
    private $attachmentInterfaceFactory;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param CommentManagement $commentManagement
     * @param CommentAttachmentResource $commentAttachmentResource
     * @param CommentAttachmentInterfaceFactory $attachmentInterfaceFactory
     * @param FileProcessor $fileProcessor
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        CommentManagement $commentManagement,
        CommentAttachmentResource $commentAttachmentResource,
        CommentAttachmentInterfaceFactory $attachmentInterfaceFactory,
        FileProcessor $fileProcessor
    ) {
        parent::__construct(
            $context,
            $logger,
            $quoteRepository,
            $negotiableQuoteManagement
        );
        $this->commentManagement = $commentManagement;
        $this->commentAttachmentResource = $commentAttachmentResource;
        $this->attachmentInterfaceFactory = $attachmentInterfaceFactory;
        $this->fileProcessor = $fileProcessor;
    }

    /**
     * Save draft quote.
     *
     * @return Redirect
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        try {
            $quoteData = $this->getPreparedQuoteData();
            $commentData = $this->getPreparedCommentData();
            $this->negotiableQuoteManagement->saveAsDraft($quoteId, $quoteData, $commentData);
            $this->deleteAttachments();
        } catch (NoSuchEntityException $e) {
            $this->addNotFoundError();
        } catch (Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError(__('Exception occurred during quote saving'));
        }

        $returnData = $this->getSuccessData($quoteId);

        return $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData($returnData);
    }

    /**
     * Delete attachments for deleted comments quote.
     *
     * @return void
     */
    private function deleteAttachments()
    {
        $commentIdsToDelete = $this->getRequest()->getParam('delFiles');
        if (!empty($commentIdsToDelete)) {
            foreach (explode(',', $commentIdsToDelete) as $id) {
                $attachment = $this->attachmentInterfaceFactory->create()->load($id);
                $this->commentAttachmentResource->delete($attachment);
            }
        }
    }

    /**
     * Get prepared quote data from request.
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function getPreparedQuoteData()
    {
        $quoteData = (array) $this->getRequest()->getParam('quote');
        $updateData = json_decode((string) $this->getRequest()->getParam('dataSend'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Unable to unserialize value. Error: " . json_last_error_msg()
            );
        }

        $quoteUpdateData = $updateData['quote'] ?? [];
        return array_merge($quoteUpdateData, $quoteData);
    }

    /**
     * Get prepared comment data from request.
     *
     * @return array
     */
    private function getPreparedCommentData()
    {
        $commentData = [
            'message' => $this->getRequest()->getParam('comment'),
            'files' => $this->fileProcessor->getFiles()
        ];
        return $commentData;
    }

    /**
     * Get success data for result.
     *
     * Success data contains success status and message
     * If files were attached success data contains file names and attachment ids as well
     *
     * @param int $quoteId
     * @return array
     */
    private function getSuccessData($quoteId)
    {
        $data =[
            'status' => 'success',
            'messages' => [['type' => 'success', 'text' => __('The changes have been saved.')]]
        ];
        if ($this->commentManagement->hasDraftComment($quoteId)) {
            $data['draftCommentFiles'] = [];
            $comment = $this->commentManagement->getQuoteComments($quoteId, true)->getFirstItem();
            foreach ($this->commentManagement->getCommentAttachments($comment->getEntityId()) as $file) {
                $data['draftCommentFiles'][] = ['name' => $file->getFileName(), 'id' => $file->getAttachmentId()];
            }
        }

        return $data;
    }
}
