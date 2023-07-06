<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyProfile;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Model\CompanyProfile class.
 */
class CompanyProfileTest extends TestCase
{
    /**
     * @var DataObjectHelper|MockObject
     */
    private $objectHelper;

    /**
     * @var CompanyProfile
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectHelper = $this->createMock(
            DataObjectHelper::class
        );
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CompanyProfile::class,
            [
                'objectHelper' => $this->objectHelper,
            ]
        );
    }

    /**
     * Test populate method.
     *
     * @param array $data
     * @param array $companyData
     * @return void
     * @dataProvider populateDataProvider
     */
    public function testPopulate(array $data, array $companyData)
    {
        $company = $this->createMock(
            CompanyInterface::class
        );
        $this->objectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with($company, $companyData, CompanyInterface::class)
            ->willReturnSelf();

        $this->model->populate($company, $data);
    }

    /**
     * Data provider for populate method.
     *
     * @return array
     */
    public function populateDataProvider()
    {
        return [
            [
                [
                    CompanyInterface::COUNTRY_ID => 'US',
                    CompanyInterface::REGION_ID => 12
                ],
                [
                    'country_id' => 'US',
                    'region_id' => 12
                ]
            ],
        ];
    }
}
