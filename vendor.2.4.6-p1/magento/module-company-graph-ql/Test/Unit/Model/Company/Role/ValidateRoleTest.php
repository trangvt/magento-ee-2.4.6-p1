<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Test\Unit\Model\Company\Role;

use Magento\CompanyGraphQl\Model\Company\Role\ValidateRole;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use PHPUnit\Framework\TestCase;

class ValidateRoleTest extends TestCase
{
    /**
     * @var ValidateRole
     */
    private $validateRole;

    protected function setUp(): void
    {
        $resourceProviderMock = $this->createMock(ProviderInterface::class);
        $this->validateRole = new ValidateRole($resourceProviderMock);

        $resources = [
            [
                'id' => 'Magento_Company::index',
                'children' => [
                    [
                        'id' => 'Magento_Sales::all',
                        'children' => [
                            [
                                'id' => 'Magento_Sales::place_order',
                                'children' => [
                                    [
                                        'id' => 'Magento_Sales::payment_account',
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'Magento_PurchaseOrder::all',
                        'children' => [
                            [
                                'id' => 'Magento_PurchaseOrder::view_purchase_orders',
                                'children' => [],
                            ],
                            [
                                'id' => 'Magento_PurchaseOrder::autoapprove_purchase_order',
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'id' => 'Magento_NegotiableQuote::all',
                        'children' => [],
                    ],
                ],
            ],
        ];
        $resourceProviderMock->method('getAclResources')
            ->willReturn($resources);
    }

    public function testExecute(): void
    {
        $roleData = [
            'name' => 'Role1',
            'permissions' => [
                'Magento_Company::index',
                'Magento_Sales::all',
                'Magento_Sales::place_order',
                'Magento_Sales::payment_account',
                'Magento_PurchaseOrder::all',
                'Magento_PurchaseOrder::view_purchase_orders',
                'Magento_PurchaseOrder::autoapprove_purchase_order',
                'Magento_NegotiableQuote::all',
            ],
        ];
        $this->validateRole->execute($roleData);
    }
}
