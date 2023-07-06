<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Customer;

use Magento\Company\Api\AuthorizationInterface;
use Magento\CompanyCredit\Block\Customer\Link;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var Link
     */
    private $model;

    public function testConstruct()
    {
        $resourceValue = 'resource_value';
        $data = [
            'resource' => $resourceValue,
        ];

        $authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Link::class,
            [
                'authorization' => $authorizationMock,
                'data' => $data,
            ]
        );

        $authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with($resourceValue);

        $this->model->toHtml();
    }

    public function testConstructWithoutResource()
    {
        $authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Link::class,
            [
                'authorization' => $authorizationMock,
            ]
        );

        $authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with(null);

        $this->model->toHtml();
    }
}
