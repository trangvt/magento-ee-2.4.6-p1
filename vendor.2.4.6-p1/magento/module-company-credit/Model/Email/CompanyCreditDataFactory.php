<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Model\Email;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\Sales\OrderLocator;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class that creates DataObject containing company credit information to use in Sender class.
 */
class CompanyCreditDataFactory
{
    /**
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CreditLimitRepositoryInterface
     */
    private $creditLimitRepository;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerViewHelper;

    /**
     * @var OrderLocator
     */
    private $orderLocator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param DataObjectProcessor $dataProcessor
     * @param CompanyRepositoryInterface $companyRepository
     * @param CreditLimitRepositoryInterface $creditLimitRepository
     * @param PriceCurrencyInterface $priceFormatter
     * @param CustomerNameGenerationInterface $customerViewHelper
     * @param OrderLocator $orderLocator
     * @param SerializerInterface $serializer
     */
    public function __construct(
        DataObjectProcessor $dataProcessor,
        CompanyRepositoryInterface $companyRepository,
        CreditLimitRepositoryInterface $creditLimitRepository,
        PriceCurrencyInterface $priceFormatter,
        CustomerNameGenerationInterface $customerViewHelper,
        OrderLocator $orderLocator,
        SerializerInterface $serializer
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->companyRepository = $companyRepository;
        $this->creditLimitRepository = $creditLimitRepository;
        $this->priceFormatter = $priceFormatter;
        $this->customerViewHelper = $customerViewHelper;
        $this->orderLocator = $orderLocator;
        $this->serializer = $serializer;
    }

    /**
     * Create an object with data merged from CreditHistory and Credit.
     *
     * @param HistoryInterface $history
     * @param CustomerInterface $customer
     * @return DataObject|null
     * @throws NoSuchEntityException
     */
    public function getCompanyCreditDataObject(
        HistoryInterface $history,
        CustomerInterface $customer
    ) {
        $mergedCompanyCreditData = null;
        $orderId = null;
        $storeId = null;
        $creditLimit = $this->creditLimitRepository->get($history->getCompanyCreditId());
        $company = $this->companyRepository->get((int)$creditLimit->getCompanyId());
        $companyCreditData = $this->dataProcessor
            ->buildOutputDataArray($history, HistoryInterface::class);
        $mergedCompanyCreditData = new DataObject($companyCreditData);
        $comment = $history->getComment() ? $this->serializer->unserialize($history->getComment()) : false;
        if (is_array($comment) && isset($comment['system']['order'])) {
            $orderId = $comment['system']['order'];
            $order = $this->orderLocator->getOrderByIncrementId($orderId);
            $storeId = $order->getStoreId();
        }
        $mergedCompanyCreditData->setData(
            'availableCredit',
            $this->priceFormatter->format(
                $history->getCreditLimit(),
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $storeId,
                $history->getCurrencyCredit()
            )
        );
        $mergedCompanyCreditData->setData(
            'outStandingBalance',
            $this->priceFormatter->format(
                $history->getBalance(),
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $storeId,
                $history->getCurrencyCredit()
            )
        );
        $historyComment = $history->getComment() ? $this->serializer->unserialize($history->getComment()) : [];
        if (isset($historyComment['system'][HistoryInterface::COMMENT_TYPE_UPDATE_EXCEED_LIMIT]['value'])) {
            $exceedLimit = (bool)$historyComment['system'][HistoryInterface::COMMENT_TYPE_UPDATE_EXCEED_LIMIT]['value'];
        } else {
            $exceedLimit = $creditLimit->getExceedLimit();
        }
        $mergedCompanyCreditData->setData(
            'exceedLimit',
            $exceedLimit ? 'allowed' : 'not allowed'
        );
        $operationAmount = $history->getAmount() * $this->getOperationAmountConversionRate($history);
        $mergedCompanyCreditData->setData(
            'operationAmount',
            $this->priceFormatter->format(
                $operationAmount,
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $storeId,
                $history->getCurrencyCredit()
            )
        );
        $mergedCompanyCreditData->setData('orderId', $orderId);
        $mergedCompanyCreditData->setData('companyName', $company->getCompanyName());
        $mergedCompanyCreditData->setData('customerName', $this->customerViewHelper->getCustomerName($customer));

        return $mergedCompanyCreditData;
    }

    /**
     * Get rate for conversion operation amount to credit currency.
     *
     * If history item does not contain currency rate,
     * return rate between base currency and operation currency.
     * Otherwise return 1.
     *
     * @param HistoryInterface $history
     * @return float
     */
    private function getOperationAmountConversionRate(HistoryInterface $history): float
    {
        $conversionRate = 1;
        $rate = (float)$history->getRate() ?: 1;
        $rateCredit = (float)$history->getRateCredit();

        if ($rateCredit) {
            $conversionRate = $rateCredit;
        } elseif ($history->getCurrencyOperation() != $history->getCurrencyCredit()) {
            $conversionRate = 1 / $rate;
        }

        return (float)$conversionRate;
    }
}
