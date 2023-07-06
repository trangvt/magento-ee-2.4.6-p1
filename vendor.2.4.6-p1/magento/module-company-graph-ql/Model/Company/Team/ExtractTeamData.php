<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Team;

use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Class for extract team data into array.
 */
class ExtractTeamData
{
    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var Structure
     */
    private Structure $structure;

    /**
     * @param Uid $uid
     * @param Structure $structure
     */
    public function __construct(Uid $uid, Structure $structure)
    {
        $this->uid = $uid;
        $this->structure = $structure;
    }

    /**
     * Extract team data into an array.
     *
     * @param TeamInterface $team
     * @return array
     */
    public function execute(TeamInterface $team): array
    {
        return [
            'id' => $this->uid->encode((string) $team->getId()),
            'name' => $team->getName(),
            'description' => $team->getDescription(),
            'structure_id' => $this->uid->encode(
                (string) $this->structure->getStructureByTeamId($team->getId())->getId()
            )
        ];
    }
}
