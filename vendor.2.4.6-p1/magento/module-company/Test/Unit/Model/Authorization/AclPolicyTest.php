<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Authorization;

use Magento\Company\Model\Authorization\AclPolicy;
use Magento\Framework\Acl;
use Magento\Framework\Acl\Builder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AclPolicyTest extends TestCase
{
    /**
     * @var Builder|MockObject
     */
    private $aclBuilder;

    /**
     * @var AclPolicy
     */
    private $aclPolicyModel;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->aclBuilder = $this->createPartialMock(
            Builder::class,
            ['getAcl']
        );
        $objectManagerHelper = new ObjectManager($this);
        $this->aclPolicyModel = $objectManagerHelper->getObject(
            AclPolicy::class,
            [
                '_aclBuilder' => $this->aclBuilder
            ]
        );
    }

    /**
     * Test isAllowed method.
     *
     * @param int $roleId
     * @param int $resourceId
     * @param int $counter
     * @param bool $expectedResult
     * @return void
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed($roleId, $resourceId, $counter, $expectedResult)
    {
        $acl = $this->createPartialMock(
            Acl::class,
            ['isAllowed']
        );
        $this->aclBuilder->expects($this->exactly($counter))->method('getAcl')->willReturn($acl);
        $acl->expects($this->exactly($counter))->method('isAllowed')->willReturn(false);
        $this->assertEquals($expectedResult, $this->aclPolicyModel->isAllowed($roleId, $resourceId));
    }

    /**
     * Data provider for isAllowed method.
     *
     * @return array
     */
    public function isAllowedDataProvider()
    {
        return [
            [0, 1, 0, true],
            [1, 1, 1, false]
        ];
    }
}
