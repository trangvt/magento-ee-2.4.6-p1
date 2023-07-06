<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Company;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\Assign;
use Magento\SharedCatalog\Model\Form\Storage\Company;
use Magento\SharedCatalog\Model\Form\Storage\CompanyFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssignTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var CompanyFactory|MockObject
     */
    protected $companyStorageFactoryMock;

    /**
     * @var Company|MockObject
     */
    protected $companyStorageMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Assign|MockObject
     */
    protected $assignController;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createPartialMock(Context::class, ['getRequest']);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->resultJsonFactoryMock =
            $this->createPartialMock(JsonFactory::class, ['create']);
        $this->companyStorageFactoryMock =
            $this->createPartialMock(CompanyFactory::class, ['create']);
        $this->companyStorageMock = $this->createPartialMock(
            Company::class,
            ['assignCompanies', 'isCompanyAssigned']
        );

        $objectManager = new ObjectManager($this);
        $this->assignController = $objectManager->getObject(
            Assign::class,
            [
                'context' => $this->contextMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'companyStorageFactory' => $this->companyStorageFactoryMock
            ]
        );
    }

    /**
     * Test for method Execute
     */
    public function testExecute()
    {
        $sameId = 12;
        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY],
                ['company_id'],
                ['is_assign']
            )
            ->willReturn($sameId);
        $this->companyStorageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->companyStorageMock);
        $json = $this->createMock(Json::class);
        $this->companyStorageMock->expects($this->any())->method('assignCompanies')->willReturnSelf();
        $json->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->companyStorageMock->expects($this->any())->method('isCompanyAssigned')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($json);

        $this->assignController->execute();
    }
}
