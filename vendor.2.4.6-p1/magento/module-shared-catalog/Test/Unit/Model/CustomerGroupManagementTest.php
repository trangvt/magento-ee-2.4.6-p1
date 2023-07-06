<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\SharedCatalog\Model\CustomerGroupManagement;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\SharedCatalog\Model\CustomerGroupManagement.
 */
class CustomerGroupManagementTest extends TestCase
{
    /**
     * @var CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->customerGroupManagement = $this->createPartialMock(
            CustomerGroupManagement::class,
            ['getGroupIdsNotInSharedCatalogs']
        );
    }

    /**
     * @dataProvider isPrimaryCatalogAvailableDataProvider
     *
     * @param int|string $customerGroupId
     * @param bool $expected
     * @return void
     */
    public function testIsPrimaryCatalogAvailable(
        $customerGroupId,
        bool $expected
    ): void {
        $this->customerGroupManagement
            ->expects($this->once())
            ->method('getGroupIdsNotInSharedCatalogs')
            ->willReturn([1, 2]);
        $this->assertEquals(
            $expected,
            $this->customerGroupManagement->isPrimaryCatalogAvailable($customerGroupId)
        );
    }

    /**
     * @return array
     */
    public function isPrimaryCatalogAvailableDataProvider(): array
    {
        return [
            'intGroupIdNotInSharedCatalog' => [1, true],
            'stringGroupIdNotInSharedCatalog' => ['1', true],
            'intGroupIdInSharedCatalog' => [5, false],
            'stringGroupIdInSharedCatalog' => ['5', false],
        ];
    }
}
