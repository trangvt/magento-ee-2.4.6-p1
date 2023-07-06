<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Model\Authorization;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Authorization\RoleLocatorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Magento\Company\Model\Authorization class.
 */
class AuthorizationTest extends TestCase
{
    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var PolicyInterface|MockObject
     */
    protected $aclPolicy;

    /**
     * @var RoleLocatorInterface|MockObject
     */
    protected $aclRoleLocator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->aclPolicy = $this->getMockBuilder(PolicyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->aclRoleLocator = $this->getMockBuilder(RoleLocatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->authorization = $objectManagerHelper->getObject(
            Authorization::class,
            [
                '_aclPolicy' => $this->aclPolicy,
                '_aclRoleLocator' => $this->aclRoleLocator,
            ]
        );
    }

    /**
     * Unit test for 'isAllowed' method.
     *
     * @param int $roleId
     * @param string $resourceId
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed($roleId, $resourceId, $expectedResult)
    {
        $this->aclPolicy->expects($this->once())->method('isAllowed')->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->authorization->isAllowed($roleId, $resourceId));
    }

    /**
     * Data provider for isAllowed method.
     *
     * @return array
     */
    public function isAllowedDataProvider()
    {
        return [
            [0, '1', false],
            [1, '1', true]
        ];
    }
}
