<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;

class Items implements BatchResolverInterface
{
    /**
     * @var RequisitionListItems
     */
    private $itemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * RequisitionListItems constructor
     *
     * @param RequisitionListItems $itemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        RequisitionListItems $itemRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        IdEncoder $idEncoder
    ) {
        $this->itemRepository = $itemRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Get all sku
     *
     * @param ExtensibleDataInterface[] $requisitionListItems
     * @return array
     */
    private function getItemsSkus(array $requisitionListItems)
    {
        $skus = [];

        /** @var RequisitionListItemInterface  $item */
        foreach ($requisitionListItems as $item) {
            $skus[] = $item->getSku();
        }
        return $skus;
    }

    /**
     * Prepare product list
     *
     * @param ProductInterface[] $productList
     * @return array
     */
    private function prepareProductList(array $productList)
    {
        $productListData = [];
        foreach ($productList as $product) {
            $productListData[$product->getSku()] = $product;
        }
        return $productListData;
    }

    /**
     * Get Requisition list data
     *
     * @param int[] $requisitionListIds
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    private function getRequisitionListData(array $requisitionListIds, int $currentPage, int $pageSize): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter("requisition_list_id", $requisitionListIds, "in")
            ->setCurrentPage($currentPage)
            ->setPageSize($pageSize)
            ->create();

        $requisitionListItems = $this->itemRepository->getList($searchCriteria)->getItems();

        $skus = $this->getItemsSkus($requisitionListItems);

        $searchCriteria = $this->searchCriteriaBuilder->addFilter("sku", $skus, "in")->create();
        $productList = $this->productRepository->getList($searchCriteria)->getItems();
        $result = [];

        $productListData = $this->prepareProductList($productList);

        foreach ($requisitionListItems as $item) {
            $product = $productListData[$item->getSku()];
            $productData = $product->getData();
            $productData['model'] = $product;
            $result[$item->getRequisitionListId()][] = [
                'uid' => $this->idEncoder->encode($item->getId()),
                'quantity' => $item->getQty(),
                'product' => $productData,
                'model' => $item
            ];
        }

        return $result;
    }

    /**
     * Returns Requisition list items
     *
     * @param ContextInterface $context
     * @param Field $field
     * @param BatchRequestItemInterface[] $requests
     * @return BatchResponse
     * @throws GraphQlInputException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $requisitionListIds = [];

        foreach ($requests as $request) {
            if (empty($request->getValue()['uid'])) {
                throw new GraphQlInputException(__('"uid" value should be specified'));
            }
            $requisitionListIds[] =  $this->idEncoder->decode($request->getValue()['uid']);
        }

        $pageSize = $requests[0]->getArgs()['pageSize'];
        $currentPage = $requests[0]->getArgs()['currentPage'];
        $requisitionListData = $this->getRequisitionListData($requisitionListIds, $currentPage, $pageSize);
        $response = new BatchResponse();

        foreach ($requests as $request) {
            $requisitionListId = $this->idEncoder->decode($request->getValue()['uid']);
            $result = isset($requisitionListData[$requisitionListId]) ? $requisitionListData[$requisitionListId] : [];
            $totalPages = (int)ceil($request->getValue()['items_count']/$pageSize);
            $response->addResponse(
                $request,
                [
                    'items' => $result,
                    'total_pages' => $totalPages,
                    'page_info' => [
                        'page_size' => $pageSize,
                        'current_page' => $currentPage,
                        'total_pages' => $totalPages
                    ],
                ]
            );
        }
        return $response;
    }
}
