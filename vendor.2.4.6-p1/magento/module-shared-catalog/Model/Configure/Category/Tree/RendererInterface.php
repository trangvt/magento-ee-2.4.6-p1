<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model\Configure\Category\Tree;

use Magento\Framework\Data\Tree\Node;

/**
 * Interface RendererInterface
 *
 * @api
 */
interface RendererInterface
{
    /**
     * Render tree data
     *
     * @param Node $rootNode
     * @return mixed
     */
    public function render(Node $rootNode);
}
