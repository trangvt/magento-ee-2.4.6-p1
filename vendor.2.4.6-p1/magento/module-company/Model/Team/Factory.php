<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\Team;

use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Create TeamInterface object from raw data
 */
class Factory
{
    /**
     * @var TeamInterfaceFactory
     */
    private $teamFactory;

    /**
     * @var DataObjectHelper
     */
    private $objectHelper;

    /**
     * @param TeamInterfaceFactory $teamFactory
     * @param DataObjectHelper $objectHelper
     */
    public function __construct(
        TeamInterfaceFactory $teamFactory,
        DataObjectHelper $objectHelper
    ) {
        $this->teamFactory = $teamFactory;
        $this->objectHelper = $objectHelper;
    }

    /**
     * Create TeamInterface object from raw data
     *
     * @param array $rawData
     * @return TeamInterface
     */
    public function create(array $rawData): TeamInterface
    {
        $team = $this->teamFactory->create();
        $this->objectHelper->populateWithArray(
            $team,
            [
                'name' => $rawData['name'] ?? '',
                'description' => $rawData['description'] ?? ''
            ],
            TeamInterface::class
        );

        return $team;
    }
}
