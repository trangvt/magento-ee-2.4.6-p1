<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Form\Storage;

use Magento\Framework\Session\Generic;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for model Form\Storage\Wizard.
 */
class WizardTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Wizard
     */
    private $wizard;

    /**
     * @var Generic|MockObject
     */
    private $session;

    /**
     * @var string
     */
    private $key = 'asdw23dcg3456745d435ff34545';

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(Generic::class)
            ->onlyMethods(['getData'])
            ->addMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wizard = $this->objectManagerHelper->getObject(
            Wizard::class,
            [
                'session' => $this->session,
                'key' => $this->key
            ]
        );
    }

    /**
     * Test for getAssignedProductSkus().
     *
     * @return void
     */
    public function testGetAssignedProductSkus(): void
    {
        $productSkus = ['sku_1', 'sku_2', 'sku_3'];
        $this->session->expects($this->once())->method('getData')->willReturn($productSkus);

        $this->assertEquals($productSkus, $this->wizard->getAssignedProductSkus());
    }

    /**
     * Test for setAssignedProductSkus().
     *
     * @return void
     */
    public function testSetAssignedProductSkus(): void
    {
        $productSkus = ['sku_1', 'sku_2', 'sku_3'];
        $this->session->expects($this->once())->method('setData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_PRODUCT_SKUS, $productSkus)->willReturnSelf();

        $this->wizard->setAssignedProductSkus($productSkus);
    }

    /**
     * Test for getUnassignedProductSkus().
     *
     * @return void
     */
    public function testGetUnassignedProductSkus(): void
    {
        $unassignedProductSkus = ['sku_1', 'sku_2'];
        $assignedProductSkus = ['sku_1', 'sku_3'];
        $this->session
            ->method('getData')
            ->willReturnOnConsecutiveCalls($unassignedProductSkus, $assignedProductSkus);

        $this->assertEquals([1 => 'sku_2'], $this->wizard->getUnassignedProductSkus());
    }

    /**
     * Test for setAssignedProductSkus().
     *
     * @return void
     */
    public function testSetUnassignedProductSkus(): void
    {
        $productSkus = ['sku_1', 'sku_2', 'sku_3'];

        $expectedProductSkus = [0 => 'sku_1', 1 => 'sku_2', 2 => 'sku_3'];
        $this->session->expects($this->once())->method('setData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_UNASSIGNED_PRODUCT_SKUS, $expectedProductSkus)
            ->willReturnSelf();

        $this->wizard->setUnassignedProductSkus($productSkus);
    }

    /**
     * Test for getAssignedCategoriesIds().
     *
     * @return void
     */
    public function testGetAssignedCategoriesIds(): void
    {
        $categoriesIds = [36, 36, 15];
        $this->session->expects($this->once())->method('getData')->willReturn($categoriesIds);

        $result = $this->wizard->getAssignedCategoriesIds();
        $this->assertEquals($categoriesIds, $result);
    }

    /**
     * Test for getUnassignedCategoriesIds().
     *
     * @return void
     */
    public function testGetUnassignedCategoriesIds(): void
    {
        $unassignedCategoriesIds = [25, 55];
        $unassignedCategoriesParamKey = $this->key . '_' . Wizard::SESSION_KEY_UNASSIGNED_CATEGORIES_IDS;

        $assignedCategoriesIds = [25, 23];
        $assignedCategoriesParamKey = $this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_CATEGORIES_IDS;
        $this->session
            ->method('getData')
            ->withConsecutive([$unassignedCategoriesParamKey], [$assignedCategoriesParamKey])
            ->willReturnOnConsecutiveCalls($unassignedCategoriesIds, $assignedCategoriesIds);

        $expects = [1 => 55];
        $result = $this->wizard->getUnassignedCategoriesIds();
        $this->assertEquals($expects, $result);
    }

    /**
     * Test for assignProducts().
     *
     * @return void
     */
    public function testAssignProducts(): void
    {
        $productSkus = $expectedProductSkus = [0 => 'sku_1', 4 => 'sku_2', 5 => 'sku_3', 2 => 'sku_4'];

        $assignedProductSkus = ['sku_1', 'sku_1', 'sku_4'];
        $this->session->expects($this->once())->method('getData')->willReturn($assignedProductSkus);

        $this->session->expects($this->once())->method('setData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_PRODUCT_SKUS, $expectedProductSkus)
            ->willReturnSelf();

        $this->wizard->assignProducts($productSkus);
    }

    /**
     * Test for unassignProducts().
     *
     * @return void
     */
    public function testUnassignProducts(): void
    {
        $unassignedProductSkus = ['sku_1', 'sku_2', 'sku_3'];
        $assignedProductSkus1 = ['sku_1', 'sku_4'];
        $resultUnassignedProductSkus = ['sku_2', 'sku_3', 'sku_1'];
        $assignedProductSkus2 = ['sku_6', 'sku_7', 'sku_8'];
        $resultAssignedProductSkus = ['sku_6', 'sku_7', 'sku_8'];

        $this->session
            ->method('getData')
            ->withConsecutive([], [], [$this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_PRODUCT_SKUS])
            ->willReturnOnConsecutiveCalls($unassignedProductSkus, $assignedProductSkus1, $assignedProductSkus2);
        $this->session
            ->method('setData')
            ->withConsecutive(
                [
                    $this->key . '_' . Wizard::SESSION_KEY_UNASSIGNED_PRODUCT_SKUS,
                    $resultUnassignedProductSkus
                ],
                [
                    $this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_PRODUCT_SKUS,
                    $resultAssignedProductSkus
                ]
            )
            ->willReturnOnConsecutiveCalls($this->session, $this->session);

        $this->wizard->unassignProducts($unassignedProductSkus);
    }

    /**
     * Test for assignCategories().
     *
     * @return void
     */
    public function testAssignCategories(): void
    {
        $categoriesIds = [36, 36, 15];

        $assignedCategoriesIds = [76, 76, 15];
        $this->session->expects($this->once())->method('getData')->willReturn($assignedCategoriesIds);

        $expectedCategoriesIds = [0 => 76, 2 => 15, 3 => 36];
        $this->session->expects($this->once())->method('setData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_CATEGORIES_IDS, $expectedCategoriesIds)
            ->willReturnSelf();

        $this->wizard->assignCategories($categoriesIds);
    }

    /**
     * Test for unassignCategories().
     *
     * @return void
     */
    public function testUnassignCategories(): void
    {
        $categoriesIds = [16, 25, 8];

        $unassignedCategoriesIds = [25, 55];
        $unassignedCategoriesParamKey = $this->key . '_' . Wizard::SESSION_KEY_UNASSIGNED_CATEGORIES_IDS;

        $assignedCategoriesIds = [25, 23];
        $assignedCategoriesParamKey = $this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_CATEGORIES_IDS;

        $resultUnassignedCategoriesIds = [55, 16, 25, 8];

        $resultAssignedCategoriesIds = [1 => 23];
        $this->session
            ->method('getData')
            ->withConsecutive([$unassignedCategoriesParamKey], [$assignedCategoriesParamKey])
            ->willReturnOnConsecutiveCalls($unassignedCategoriesIds, $assignedCategoriesIds, $assignedCategoriesIds);
        $this->session
            ->method('setData')
            ->withConsecutive(
                [
                    $this->key . '_' . Wizard::SESSION_KEY_UNASSIGNED_CATEGORIES_IDS,
                    $resultUnassignedCategoriesIds
                ],
                [
                    $this->key . '_' . Wizard::SESSION_KEY_ASSIGNED_CATEGORIES_IDS,
                    $resultAssignedCategoriesIds
                ]
            )
            ->willReturnOnConsecutiveCalls($this->session, $this->session);

        $this->wizard->unassignCategories($categoriesIds);
    }

    /**
     * Test for isProductAssigned().
     *
     * @return void
     */
    public function testIsProductAssigned(): void
    {
        $productId = 34;

        $productIds = [34, 36, 15];
        $this->session->expects($this->once())->method('getData')->willReturn($productIds);

        $expected = true;
        $result = $this->wizard->isProductAssigned($productId);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for setTierPrices method.
     *
     * @return void
     */
    public function testSetTierPrices(): void
    {
        $tierPriceToAdd = ['sku_1' => [['qty' => 1, 'website_id' => 3]]];
        $presentTierPrice = ['sku_1' => [['qty' => 1, 'website_id' => 2]]];
        $this->session->expects($this->once())->method('getData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES)->willReturn($presentTierPrice);
        $this->session->expects($this->once())->method('setData')
            ->with(
                $this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES,
                ['sku_1' => [$presentTierPrice['sku_1'][0], $tierPriceToAdd['sku_1'][0]]]
            )->willReturnSelf();
        $this->wizard->setTierPrices($tierPriceToAdd);
    }

    /**
     * Test for deleteTierPrice method.
     *
     * @return void
     */
    public function testDeleteTierPrice(): void
    {
        $productId = 1;
        $tierPriceToDelete = ['qty' => 1, 'website_id' => 3, 'is_changed' => true, 'is_deleted' => true];
        $presentTierPrice = ['qty' => 1, 'website_id' => 2, 'is_changed' => true, 'is_deleted' => true];
        $sessionData = [$productId => [$presentTierPrice, $tierPriceToDelete]];
        $this->session->expects($this->exactly(2))->method('getData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES)->willReturn($sessionData);
        $this->session->expects($this->once())->method('setData')
            ->with(
                $this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES,
                [$productId => [$presentTierPrice, $tierPriceToDelete]]
            )->willReturnSelf();
        $this->wizard->deleteTierPrice($productId, $tierPriceToDelete['qty'], $tierPriceToDelete['website_id']);
    }

    /**
     * Test for deleteTierPrices method.
     *
     * @return void
     */
    public function testDeleteTierPrices(): void
    {
        $productId = 1;
        $sessionData = [$productId => [['qty' => 1, 'website_id' => 2, 'is_changed' => true, 'is_deleted' => true]]];
        $this->session->expects($this->atLeastOnce())->method('getData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES)->willReturn($sessionData);
        $this->session->expects($this->once())->method('setData')
            ->with(
                $this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES,
                $sessionData
            )->willReturnSelf();
        $this->wizard->deleteTierPrices($productId);
    }

    /**
     * Test for getProductPrice method.
     *
     * @return void
     */
    public function testGetProductPrice(): void
    {
        $productId = 1;
        $websiteId = 2;
        $sessionData = [$productId => [['qty' => 2, 'website_id' => 2], ['qty' => 1, 'website_id' => 2]]];
        $this->session->expects($this->once())->method('getData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES)->willReturn($sessionData);
        $this->assertEquals($sessionData[$productId][1], $this->wizard->getProductPrice($productId, $websiteId));
    }

    /**
     * Test for getProductPrices method.
     *
     * @return void
     */
    public function testGetProductPrices(): void
    {
        $productSku = 'sku_1';
        $sessionData = [$productSku => [['qty' => 2, 'website_id' => 2], ['qty' => 1, 'website_id' => 2]]];
        $this->session->expects($this->once())->method('getData')
            ->with($this->key . '_' . Wizard::SESSION_KEY_PRODUCT_TIER_PRICES)->willReturn($sessionData);
        $this->assertEquals([2 => $sessionData[$productSku][1]], $this->wizard->getProductPrices($productSku));
    }
}
