<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Link;

use Magento\Company\Block\Link\Delimiter;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class DelimiterTest extends TestCase
{
    /**
     * @var Delimiter
     */
    private $model;

    public function testConstruct()
    {
        $resourceValueOne = 'resource_value_1';
        $resourceValueTwo = 'resource_value_2';
        $data = [
            'resources' => [
                $resourceValueOne,
                $resourceValueTwo,
            ],
        ];

        $companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Delimiter::class,
            [
                'companyContext' => $companyContextMock,
                'data' => $data,
            ]
        );

        $companyContextMock->expects($this->exactly(2))
            ->method('isResourceAllowed')
            ->willReturnMap([
                [$resourceValueOne, false],
                [$resourceValueTwo, false],
            ]);
        $companyContextMock->expects($this->once())
            ->method('isModuleActive')
            ->willReturn(true);

        $this->model->toHtml();
    }

    public function testConstructWithoutResource()
    {
        $companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Delimiter::class,
            [
                'companyContext' => $companyContextMock,
            ]
        );

        $companyContextMock->expects($this->never())
            ->method('isResourceAllowed');
        $companyContextMock->expects($this->once())
            ->method('isModuleActive')
            ->willReturn(true);

        $this->model->toHtml();
    }
}
