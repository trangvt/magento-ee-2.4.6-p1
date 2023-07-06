<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Controller\Sku;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\QuickOrder\Controller\Sku\UploadFile;
use PHPUnit\Framework\TestCase;

class UploadFileTest extends TestCase
{
    /**
     * @var \Magento\QuickOrder\Controller\Index\Download
     */
    protected $controller;

    /**
     * @var RequestInterface
     */
    protected $requestMock;

    public function testExecute()
    {
        $context = $this->createMock(Context::class);
        $helper = $this->createMock(Data::class);
        $helper->expects($this->any())
            ->method('isSkuFileUploaded')->willReturn(true);
        $helper->expects($this->any())
            ->method('processSkuFileUploading')->willReturn(['test', 'test 2']);
        $this->requestMock = $this->createMock(Http::class);
        $context->expects($this->any())
            ->method('getRequest')->willReturn($this->requestMock);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            UploadFile::class,
            [
                'context' => $context,
                'advancedCheckoutHelper' => $helper
            ]
        );

        $this->controller->execute();
    }
}
