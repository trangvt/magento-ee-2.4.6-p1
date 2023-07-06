<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Link;

use Magento\Company\Block\Link\Current;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CurrentTest extends TestCase
{
    /**
     * @var Current
     */
    private $model;

    public function testConstruct()
    {
        $resourceValue = 'resource_value';
        $data = [
            'resource' => $resourceValue,
        ];

        $companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Current::class,
            [
                'companyContext' => $companyContextMock,
                'data' => $data,
            ]
        );

        $companyContextMock->expects($this->once())
            ->method('isResourceAllowed')
            ->with($resourceValue);

        $this->model->toHtml();
    }

    public function testConstructWithoutResource()
    {
        $companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Current::class,
            [
                'companyContext' => $companyContextMock,
            ]
        );

        $companyContextMock->expects($this->once())
            ->method('isResourceAllowed')
            ->with(null);

        $this->model->toHtml();
    }
}
