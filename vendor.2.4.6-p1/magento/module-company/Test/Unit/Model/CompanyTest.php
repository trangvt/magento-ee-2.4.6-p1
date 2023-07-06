<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Model\Company;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    /**
     * @var Company
     */
    protected $company;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->company = $objectManager->getObject(Company::class);
    }

    public function testSaveAdvancedCustomAttributes()
    {
        $street = ['test', '123'];
        $this->company->setStreet($street);

        $this->assertEquals($street, $this->company->getStreet());
    }
}
