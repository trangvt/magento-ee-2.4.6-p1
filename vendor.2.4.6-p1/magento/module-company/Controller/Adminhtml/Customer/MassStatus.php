<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Adminhtml\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Customer\Controller\Adminhtml\Index\AbstractMassAction;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Class MassStatus
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassStatus extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CompanyCustomerInterfaceFactory
     */
    private $companyCustomerFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyCustomerInterfaceFactory|null $companyCustomerFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository,
        CompanyCustomerInterfaceFactory $companyCustomerFactory = null
    ) {
        parent::__construct($context, $filter, $collectionFactory);
        $this->customerRepository = $customerRepository;
        $this->companyCustomerFactory = $companyCustomerFactory ?: ObjectManager::getInstance()
            ->get(CompanyCustomerInterfaceFactory::class);
    }

    /**
     * @inheritDoc
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function massAction(AbstractCollection $collection)
    {
        $status = (int)$this->getRequest()->getParam('status');
        $customersUpdated = 0;
        foreach ($collection->getAllIds() as $customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $customerExtensionAttributes = $customer->getExtensionAttributes();
            /** @var CompanyCustomerInterface $companyCustomerAttributes */
            $companyCustomerAttributes = $customerExtensionAttributes->getCompanyAttributes();
            if (!$companyCustomerAttributes) {
                $companyCustomerAttributes = $this->companyCustomerFactory->create();
            }
            $companyCustomerAttributes->setStatus($status);
            $customerExtensionAttributes->setCompanyAttributes($companyCustomerAttributes);

            try {
                $this->customerRepository->save($customer);
                $customersUpdated++;
            } catch (CouldNotSaveException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        if ($customersUpdated) {
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) were updated.', $customersUpdated));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/index/index');

        return $resultRedirect;
    }
}
