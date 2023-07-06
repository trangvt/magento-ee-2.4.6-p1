<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 */
class MassDelete extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Company::delete';

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CompanyRepositoryInterface $companyRepository
    ) {
        parent::__construct($context, $filter, $collectionFactory);
        $this->companyRepository = $companyRepository;
    }

    /**
     * Perform mass action.
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \InvalidArgumentException
     */
    protected function massAction(AbstractCollection $collection)
    {
        $companiesDeleted = 0;
        foreach ($collection->getAllIds() as $companyId) {
            $this->companyRepository->deleteById($companyId);
            $companiesDeleted++;
        }

        if ($companiesDeleted) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were deleted.', $companiesDeleted));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
