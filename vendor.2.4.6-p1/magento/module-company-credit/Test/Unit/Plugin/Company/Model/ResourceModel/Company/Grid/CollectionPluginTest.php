<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Company\Model\ResourceModel\Company\Grid;

use Magento\Company\Model\ResourceModel\Company\Grid\Collection;
use Magento\CompanyCredit\Plugin\Company\Model\ResourceModel\Company\Grid\CollectionPlugin;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CollectionPluginTest extends TestCase
{
    /**
     * @var CollectionPlugin
     */
    private $collectionPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->collectionPlugin = $objectManager->getObject(
            CollectionPlugin::class
        );
    }

    /**
     * Test beforeLoadWithFilter method.
     *
     * @return void
     */
    public function testAfterGetCompanyResultData()
    {
        $companyCollection = $this->createPartialMock(
            Collection::class,
            ['getSelect', 'getTable']
        );
        $select = $this->createPartialMock(
            Select::class,
            ['joinLeft']
        );
        $companyCollection->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);
        $companyCollection->expects($this->once())
            ->method('getTable')
            ->with('company_credit')
            ->willReturn('company_credit');
        $select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['company_credit' => 'company_credit'],
                'company_credit.company_id = main_table.entity_id',
                ['company_credit.credit_limit', 'company_credit.balance', 'company_credit.currency_code']
            )
            ->willReturnSelf();
        $this->collectionPlugin->beforeLoadWithFilter($companyCollection);
    }
}
