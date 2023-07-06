<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\Company\Plugin\Sales\Controller\Order;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Order\Reorder;

/**
 * Class ReorderPlugin.
 *
 * Restrict access to the reorder functionality depending on permissions for company users.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderPlugin
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @param UserContextInterface $userContext
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param AuthorizationInterface $authorization
     * @param Structure $companyStructure
     * @param CompanyContext $companyContext
     */
    public function __construct(
        UserContextInterface $userContext,
        RedirectFactory $resultRedirectFactory,
        OrderRepositoryInterface $orderRepository,
        RequestInterface $request,
        AuthorizationInterface $authorization,
        Structure $companyStructure,
        CompanyContext $companyContext
    ) {
        $this->userContext = $userContext;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->authorization = $authorization;
        $this->companyStructure = $companyStructure;
        $this->companyContext = $companyContext;
    }

    /**
     * View around execute plugin.
     *
     * @param Reorder $subject
     * @param \Closure $proceed
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        Reorder $subject,
        \Closure $proceed
    ) {
        $customerId = $this->userContext->getUserId();
        if ($customerId) {
            $orderId = $this->request->getParam('order_id');
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $exception) {
                return $proceed();
            }

            if (!$this->canReorder($order)) {
                $resultRedirect = $this->resultRedirectFactory->create();

                if ($this->companyContext->isCurrentUserCompanyUser()) {
                    $resultRedirect->setPath('company/accessdenied');
                } else {
                    $resultRedirect->setPath('noroute');
                }

                return $resultRedirect;
            }
        }

        return $proceed();
    }

    /**
     * Order can reordered.
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function canReorder(OrderInterface $order)
    {
        $customerId = $this->userContext->getUserId();
        $orderOwnerId = $order->getCustomerId();
        if ($orderOwnerId != $customerId &&
            (
                !$this->authorization->isAllowed('Magento_Sales::view_orders_sub') ||
                !$this->companyContext->isModuleActive()
            )
        ) {
            return false;
        }

        if ($this->companyContext->isCurrentUserCompanyUser()
            && !$this->authorization->isAllowed('Magento_Sales::view_orders')) {
            return false;
        }

        $subCustomers = $this->companyStructure->getAllowedChildrenIds($customerId);
        if (!in_array($orderOwnerId, $subCustomers) && $orderOwnerId != $customerId) {
            return false;
        }

        return true;
    }
}
