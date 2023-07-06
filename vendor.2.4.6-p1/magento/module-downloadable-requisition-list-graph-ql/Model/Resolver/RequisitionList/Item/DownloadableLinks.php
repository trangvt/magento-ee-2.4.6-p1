<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableRequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Catalog\Model\Product;
use Magento\DownloadableGraphQl\Model\ConvertLinksToArray;
use Magento\DownloadableGraphQl\Model\GetDownloadableProductLinks;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\RequisitionListItem;

class DownloadableLinks implements ResolverInterface
{
    /**
     * @var GetDownloadableProductLinks
     */
    private $getDownloadableProductLinks;

    /**
     * @var ConvertLinksToArray
     */
    private $convertLinksToArray;

    /**
     * Links constructor
     *
     * @param GetDownloadableProductLinks $getDownloadableProductLinks
     * @param ConvertLinksToArray $convertLinksToArray
     */
    public function __construct(
        GetDownloadableProductLinks $getDownloadableProductLinks,
        ConvertLinksToArray $convertLinksToArray
    ) {
        $this->getDownloadableProductLinks = $getDownloadableProductLinks;
        $this->convertLinksToArray = $convertLinksToArray;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !isset($value['product']['model'])) {
            throw new LocalizedException(__('"model" values should be specified'));
        }

        /** @var RequisitionListItem $requisitionListItem */
        $requisitionListItem = $value['model'];

        /** @var Product $product */
        $product = $value['product']['model'];

        $selectedLinksIds = $product->getLinksPurchasedSeparately() ?
            explode(',', $requisitionListItem->getOptions()['downloadable_link_ids']) :
            [];

        $links = $this->getDownloadableProductLinks->execute($product, $selectedLinksIds);

        return $this->convertLinksToArray->execute($links);
    }
}
