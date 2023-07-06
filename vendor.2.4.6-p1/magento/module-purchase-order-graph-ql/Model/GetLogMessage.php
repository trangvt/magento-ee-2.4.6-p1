<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Exception;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;

/**
 * Retrieve history log message
 */
class GetLogMessage
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerNameGenerationInterface
     */
    private CustomerNameGenerationInterface $customerNameGeneration;

    /**
     * @var array
     */
    private array $messages;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerNameGenerationInterface $customerNameGeneration
     * @param SerializerInterface $serializer
     * @param array $messages
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerNameGenerationInterface $customerNameGeneration,
        SerializerInterface $serializer,
        array $messages = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerNameGeneration = $customerNameGeneration;
        $this->serializer = $serializer;
        $this->messages = $messages;
    }

    /**
     * Get log message from log entry.
     *
     * @param PurchaseOrderLogInterface $log
     * @return string
     */
    public function execute(PurchaseOrderLogInterface $log): string
    {
        $requestLog = $log->getRequestLog();
        try {
            $data = $this->serializer->unserialize($requestLog);
        } catch (Exception $exception) {
            return is_string($requestLog) ? $requestLog : '';
        }
        if (!$data
            || !is_array($data)
            || !isset($this->messages[$data['action']])
            || !isset($this->messages[$data['action']]['message'])
            || !isset($data['params'])
            || !is_array($data['params'])
        ) {
            return '';
        }

        $action = $data['action'];
        $message = $this->messages[$action]['message'];
        $params = $this->getTranslatedParams($action, $data['params']);

        return __($message, $params) . $this->getCreatorSuffix($log);
    }

    /**
     * Retrieve log entry creator name as a suffix to the log message
     *
     * @param PurchaseOrderLogInterface $log
     * @return string
     */
    private function getCreatorSuffix(PurchaseOrderLogInterface $log): string
    {
        if (!$log->getOwnerId()) {
            return '';
        }

        try {
            $creatorName = $this->customerNameGeneration->getCustomerName(
                $this->customerRepository->getById($log->getOwnerId())
            );
        } catch (Exception $exception) {
            return '';
        }

        return ' ' . __('By %1', $creatorName);
    }

    /**
     * Translate required parameters
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    private function getTranslatedParams(string $action, array $params): array
    {
        if (empty($this->messages[$action]['translate_params'])) {
            return $params;
        }
        foreach ($params as $key => $param) {
            if (in_array($key, $this->messages[$action]['translate_params'])) {
                $params[$key] = __($param);
            }
        }
        return $params;
    }
}
