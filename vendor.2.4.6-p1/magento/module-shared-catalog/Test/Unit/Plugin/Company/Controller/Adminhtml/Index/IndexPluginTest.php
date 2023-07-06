<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Company\Controller\Adminhtml\Index;

use Magento\Company\Controller\Adminhtml\Index\Index;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Plugin\Company\Controller\Adminhtml\Index\IndexPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexPluginTest extends TestCase
{
    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    protected $sharedCatalogManagement;

    /**
     * @var IndexPlugin|MockObject
     */
    protected $indexMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $publicCatalogExists = false;
        $sharedCatalogCreateUrl = 'test url';
        $this->messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['addError']
        );
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(
                __(
                    'Please <a href="%1">create</a> at least a public shared catalog to proceed.',
                    $sharedCatalogCreateUrl
                )
            );
        $this->urlBuilder = $this->getMockForAbstractClass(
            UrlInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getUrl']
        );
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('shared_catalog/sharedCatalog/create')
            ->willReturn($sharedCatalogCreateUrl);
        $this->sharedCatalogManagement = $this->getMockForAbstractClass(
            SharedCatalogManagementInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isPublicCatalogExist']
        );
        $this->sharedCatalogManagement->expects($this->once())
            ->method('isPublicCatalogExist')
            ->willReturn($publicCatalogExists);
        $objectManager = new ObjectManager($this);
        $this->indexMock = $objectManager->getObject(
            IndexPlugin::class,
            [
                'messageManager' => $this->messageManager,
                'urlBuilder' => $this->urlBuilder,
                'sharedCatalogManagement' => $this->sharedCatalogManagement,
            ]
        );
    }

    /**
     * Test beforeExecute() method
     */
    public function testBeforeExecute()
    {
        $subject = $this->createMock(Index::class);
        $this->indexMock->beforeExecute($subject);
    }
}
