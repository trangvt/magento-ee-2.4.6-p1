<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RequisitionList\Controller\Items;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionListProduct;
use Psr\Log\LoggerInterface;

/**
 * Check if product(s) in cart exist in requisition list
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartValidation implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * Constructor
     *
     * @param JsonFactory $jsonResultFactory
     * @param RequestValidator $requestValidator
     * @param RequestInterface $request
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param RequisitionListProduct $requisitionListProduct
     * @param Configurable $configurable
     * @param LoggerInterface $logger
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        JsonFactory $jsonResultFactory,
        RequestValidator $requestValidator,
        RequestInterface $request,
        RequisitionListRepositoryInterface $requisitionListRepository,
        RequisitionListProduct $requisitionListProduct,
        Configurable $configurable,
        LoggerInterface $logger,
        MessageManagerInterface $messageManager
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->requestValidator = $requestValidator;
        $this->request = $request;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->requisitionListProduct = $requisitionListProduct;
        $this->configurable = $configurable;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $listId = $this->request->getParam('list_id');
        $listName = $this->request->getParam('list_name');

        $result = $this->requestValidator->getResult($this->request);
        if ($result) {
            $this->messageManager->addErrorMessage(
                __(
                    'All items in your Shopping Cart could not be added to the "%1" requisition list.',
                    $listName
                )
            );
            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_BAD_REQUEST,
                [
                    'hideAlert' => true
                ]
            );
        }

        $preparedProductData = $this->requisitionListProduct->prepareMultipleProductData(
            $this->request->getParam('productData')
        );

        try {
            $requisitionList = $this->requisitionListRepository->get($listId);
            $items = $requisitionList->getItems();

            $productExists = false;

            if (!empty($items)) {
                $productExists = $this->validateMultipleProductInRequisitionList(
                    $preparedProductData,
                    $requisitionList
                );
            }

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Response::HTTP_OK,
                [
                    'data' => [
                        'productExists' => $productExists
                    ]
                ]
            );
        } catch (NoSuchEntityException $e) {
            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_NOT_FOUND,
                [
                    'message' => $e->getMessage()
                ]
            );
        } catch (LocalizedException $e) {
            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_BAD_REQUEST,
                [
                    'message' => $e->getMessage()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_INTERNAL_ERROR,
                [
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Validate multiple products if already exist in requisition list.
     *
     * @param DataObject[] $preparedProductData
     * @param RequisitionListInterface $requisitionList
     * @return bool
     */
    private function validateMultipleProductInRequisitionList($preparedProductData, $requisitionList)
    {
        $productExists = false;

        foreach ($preparedProductData as $productData) {
            $options = is_array($productData->getOptions()) ? $productData->getOptions() : [];
            $product = $this->requisitionListProduct->getProduct($productData->getSku());
            if (isset($options['super_attribute'])) {
                $product = $this->configurable->getProductByAttributes($options['super_attribute'], $product);
            }

            $productExists = $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                $options
            );

            if ($productExists) {
                break;
            }
        }

        return $productExists;
    }

    /**
     * Create and return JSON Result with assigned response code and payload
     *
     * @param int $responseCode
     * @param array $additionalResponsePayload
     * @return JsonResult
     */
    private function getJsonResultWithAssignedResponseCodeAndPayload(
        int $responseCode,
        array $additionalResponsePayload
    ) {
        $jsonResult = $this->jsonResultFactory->create();

        $jsonResult
            ->setHttpResponseCode($responseCode)
            ->setData(
                array_merge(
                    [
                        'success' => $responseCode === Response::HTTP_OK
                    ],
                    $additionalResponsePayload
                )
            );

        return $jsonResult;
    }
}
