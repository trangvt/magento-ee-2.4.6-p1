<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RequisitionList\Controller\Item;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionListProduct;
use Psr\Log\LoggerInterface;

/**
 * Check if product in cart exists in requisition list
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartValidation implements HttpPostActionInterface
{
    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @param RequisitionListProduct $requisitionListProduct
     * @param RequestValidator $requestValidator
     * @param RequestInterface $request
     * @param JsonFactory $jsonResultFactory
     * @param LoggerInterface $logger
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param Configurable $configurable
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        RequisitionListProduct $requisitionListProduct,
        RequestValidator $requestValidator,
        RequestInterface $request,
        JsonFactory $jsonResultFactory,
        LoggerInterface $logger,
        RequisitionListRepositoryInterface $requisitionListRepository,
        Configurable $configurable,
        MessageManagerInterface $messageManager
    ) {
        $this->requisitionListProduct = $requisitionListProduct;
        $this->requestValidator = $requestValidator;
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->configurable = $configurable;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $listId = $this->request->getParam('list_id');
        $listName = $this->request->getParam('list_name');

        $preparedProductData = $this->requisitionListProduct->prepareProductData(
            $this->request->getParam('product_data')
        );

        $product = $this->requisitionListProduct->getProduct($preparedProductData->getSku());

        $productName = $product ? $product->getName() : null;

        if ($listId === null) {
            $this->addUserFriendlyErrorMessagesBasedOnListNameAndProductName($listName, $productName);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_BAD_REQUEST,
                ['message' => __('Invalid request, missing parameter list_id')]
            );
        }

        $result = $this->requestValidator->getResult($this->request);

        if ($result) {
            $this->addUserFriendlyErrorMessagesBasedOnListNameAndProductName($listName, $productName);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_BAD_REQUEST,
                ['message' => __('Invalid request, please try again.')]
            );
        }

        if (!$product) {
            $this->addUserFriendlyErrorMessagesBasedOnListNameAndProductName($listName, $productName);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_NOT_FOUND,
                ['message' => __('Product with requested SKU could not be found.')]
            );
        }

        try {
            $requisitionList = $this->requisitionListRepository->get($listId);

            $options = (array) $preparedProductData->getOptions();

            if (isset($options['super_attribute'])) {
                $configurableProduct = $this->configurable->getProductByAttributes(
                    $options['super_attribute'],
                    $product
                );

                $product = $configurableProduct ?? $product;
            }

            $productExists = $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                $options
            );

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Response::HTTP_OK,
                [
                    'data' => [
                        'productExists' => $productExists
                    ]
                ]
            );
        } catch (NoSuchEntityException $e) {
            $this->addUserFriendlyErrorMessagesBasedOnListNameAndProductName($listName, $productName);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_NOT_FOUND,
                [
                    'message' => $e->getMessage()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);

            $this->addUserFriendlyErrorMessagesBasedOnListNameAndProductName($listName, $productName);

            return $this->getJsonResultWithAssignedResponseCodeAndPayload(
                Exception::HTTP_INTERNAL_ERROR,
                [
                    'message' => __('Something went wrong.')
                ]
            );
        }
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

    /**
     * Add user friendly error messages to session, to be fetched and rendered on jQuery's ajax complete hook
     *
     * @param string|null $listName
     * @param string|null $productName
     */
    private function addUserFriendlyErrorMessagesBasedOnListNameAndProductName(
        ?string $listName = null,
        ?string $productName = null
    ) {
        $productName = $productName ?? __('The product');

        if (is_string($listName)) {
            $this->messageManager->addErrorMessage(
                __(
                    '%1 could not be added to the "%2" requisition list.',
                    $productName,
                    $listName
                )
            );
        } else {
            $this->messageManager->addErrorMessage(
                __(
                    '%1 could not be added to the requisition list.',
                    $productName
                )
            );
        }
    }
}
