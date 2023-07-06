<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Framework\Model\ActionValidator;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Plugin\Framework\Model\ActionValidator\RemoveActionPlugin;
use Magento\Customer\Model\Customer;
use Magento\Framework\Model\ActionValidator\RemoveAction;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveActionPluginTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var RemoveActionPlugin
     */
    private $removeActionPlugin;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(
            UserContextInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getUserId']
        );
        $this->structureManager = $this->createPartialMock(
            Structure::class,
            ['getAllowedIds']
        );

        $objectManager = new ObjectManager($this);
        $this->removeActionPlugin = $objectManager->getObject(
            RemoveActionPlugin::class,
            [
                'userContext' => $this->userContext,
                'structureManager' => $this->structureManager,
            ]
        );
    }

    /**
     * Test aroundIsAllowed method.
     *
     * @param int $customerId
     * @param int $currentCustomerId
     * @param bool $expectedResult
     * @return void
     * @dataProvider aroundIsAllowedDataProvider
     */
    public function testAroundIsAllowed($customerId, $currentCustomerId, $expectedResult)
    {
        $subject = $this->createMock(
            RemoveAction::class
        );
        $model = $this->createPartialMock(
            Customer::class,
            ['getId']
        );
        $proceed = function ($model) {
            return false;
        };
        $model->expects($this->once())->method('getId')->willReturn($customerId);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($currentCustomerId);
        $this->structureManager->expects($this->once())->method('getAllowedIds')->willReturn(['users' => [1]]);

        $this->assertEquals($expectedResult, $this->removeActionPlugin->aroundIsAllowed($subject, $proceed, $model));
    }

    /**
     * Dara provider for aroundIsAllowed method.
     *
     * @return array
     */
    public function aroundIsAllowedDataProvider()
    {
        return [
            [1, 2, true],
            [1, 1, false],
        ];
    }
}
