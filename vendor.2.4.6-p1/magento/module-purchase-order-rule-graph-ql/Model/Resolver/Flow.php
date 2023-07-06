<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\Flow\GetEvent;

/**
 * Resolver for the purchase order approval flow
 */
class Flow implements ResolverInterface
{
    /**
     * @var AppliedRuleRepositoryInterface
     */
    private AppliedRuleRepositoryInterface $appliedRuleRepository;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository;

    /**
     * @var GetEvent
     */
    private GetEvent $getEvent;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param GetEvent $getEvent
     * @param ResolverAccess $resolverAccess
     * @param Uid $uid
     * @param array $allowedResources
     */
    public function __construct(
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        GetEvent $getEvent,
        ResolverAccess $resolverAccess,
        Uid $uid,
        array $allowedResources = []
    ) {
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->getEvent = $getEvent;
        $this->resolverAccess = $resolverAccess;
        $this->uid = $uid;
        $this->allowedResources = $allowedResources;
    }

    /**
     * Resolve PurchaseOrderApprovalRuleMetadata type
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
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
        if (empty($value['uid'])) {
            throw new GraphQlInputException(__('"uid" value must be specified.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        return array_map(
            function (AppliedRuleInterface $appliedRule) {
                return [
                    'rule_name' => $appliedRule->getRule()->getName(),
                    'events' => array_map(
                        function (AppliedRuleApproverInterface $approver) {
                            return $this->getEvent->execute($approver);
                        },
                        $this->appliedRuleApproverRepository->getListByAppliedRuleId((int) $appliedRule->getId())
                            ->getItems()
                    )
                ];
            },
            $this->appliedRuleRepository->getListByPurchaseOrderId(
                (int) $this->uid->decode($value['uid'])
            )->getItems()
        );
    }
}
