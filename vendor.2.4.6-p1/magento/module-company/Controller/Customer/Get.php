<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Customer;

use Magento\Company\Api\AclInterface;
use Magento\Company\Controller\AbstractAction;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\Form\Element\Multiline;
use Psr\Log\LoggerInterface;

/**
 * Controller for retrieving customer info on the frontend.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Get extends AbstractAction implements HttpGetActionInterface
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param Structure $structureManager
     * @param AclInterface $acl
     * @param EavConfig|null $eavConfig
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        Structure $structureManager,
        AclInterface $acl,
        ?EavConfig $eavConfig = null
    ) {
        parent::__construct($context, $companyContext, $logger);
        $this->acl = $acl;
        $this->customerRepository = $customerRepository;
        $this->structureManager = $structureManager;
        $this->eavConfig = $eavConfig ?: ObjectManager::getInstance()
            ->get(EavConfig::class);
    }

    /**
     * Get customer action.
     *
     * @return Json
     */
    public function execute()
    {
        $request = $this->getRequest();

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        $customerId = $request->getParam('customer_id');

        if (!in_array($customerId, $allowedIds['users'])) {
            return $this->jsonError(__('You are not allowed to do this.'));
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $companyAttributes = null;
            if ($customer->getExtensionAttributes() !== null
                && $customer->getExtensionAttributes()->getCompanyAttributes() !== null
            ) {
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            }
            $this->setCustomerCustomDateAttribute($customer);
            $this->setCustomerCustomMultilineAttribute($customer);
        } catch (LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->handleJsonError();
        }

        $customerData = $customer->__toArray();
        if ($companyAttributes !== null) {
            $customerData['extension_attributes[company_attributes][job_title]'] = $companyAttributes->getJobTitle();
            $customerData['extension_attributes[company_attributes][telephone]'] = $companyAttributes->getTelephone();
            $customerData['extension_attributes[company_attributes][status]'] = $companyAttributes->getStatus();
        }
        $roles = $this->acl->getRolesByUserId($customerId);
        if (count($roles)) {
            foreach ($roles as $role) {
                $customerData['role'] = $role->getId();
                break;
            }
        }
        return $this->jsonSuccess($customerData);
    }

    /**
     * Get attribute type for upcoming validation.
     *
     * @param AbstractAttribute|Attribute $attribute
     * @return string
     */
    private function getAttributeType(AbstractAttribute $attribute): string
    {
        $frontendInput = $attribute->getFrontendInput();
        if ($attribute->usesSource() && in_array($frontendInput, ['select', 'multiselect', 'boolean'])) {
            return $frontendInput;
        } elseif ($attribute->isStatic()) {
            return $frontendInput == 'date' ? 'datetime' : 'varchar';
        } else {
            return $attribute->getBackendType();
        }
    }

    /**
     * Set customer custom date attribute
     *
     * @param CustomerInterface $customer
     * @throws LocalizedException
     */
    private function setCustomerCustomDateAttribute(CustomerInterface $customer): void
    {
        if ($customer->getCustomAttributes() !== null) {
            $customAttributes = $customer->getCustomAttributes();
            foreach ($customAttributes as $customAttribute) {
                $attributeCode = $customAttribute->getAttributeCode();
                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
                $attributeType = $this->getAttributeType($attribute);
                if ($attributeType === 'datetime') {
                    $date = new \DateTime($customAttribute->getValue());
                    $customAttribute->setValue($date->format('m/d/Y'));
                }
                $customAttribute->setData('attributeType', $attributeType);
            }
        }
    }

    /**
     * Set customer custom multiline attribute
     *
     * @param CustomerInterface $customer
     * @throws LocalizedException
     */
    private function setCustomerCustomMultilineAttribute(CustomerInterface $customer): void
    {
        if ($customer->getCustomAttributes() !== null) {
            $customAttributes = $customer->getCustomAttributes();
            foreach ($customAttributes as $customAttribute) {
                $attributeCode = $customAttribute->getAttributeCode();
                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
                $attributeType = $attribute->getFrontendInput();
                if ($attributeType === Multiline::NAME) {
                    $customAttribute->setData('attributeType', $attributeType);
                }
            }
        }
    }
}
