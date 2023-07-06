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
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\MassAssign;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\Company;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\CompanyFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\MassAssign class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassAssignTest extends TestCase
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
     * @var Filter|MockObject
     */
    protected $filterMock;

    /**
     * @var CompanyFactory|MockObject
     */
    protected $companyCollectionFactoryMock;

    /**
     * @var Company|MockObject
     */
    protected $companyCollectionMock;

    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\CompanyFactory|MockObject
     */
    protected $companyStorageFactoryMock;

    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\Company|MockObject
     */
    protected $companyStorageMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var MassAssign|MockObject
     */
    protected $massAssignController;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createPartialMock(Context::class, ['getRequest']);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->filterMock = $this->createPartialMock(Filter::class, ['getCollection']);
        $this->resultJsonFactoryMock =
            $this->createPartialMock(JsonFactory::class, ['create']);
        $this->companyCollectionFactoryMock = $this->createPartialMock(
            CompanyFactory::class,
            ['create']
        );
        $this->companyCollectionMock = $this->createPartialMock(
            Company::class,
            ['getItems']
        );
        $this->companyStorageFactoryMock =
            $this->createPartialMock(\Magento\SharedCatalog\Model\Form\Storage\CompanyFactory::class, ['create']);
        $this->companyStorageMock = $this->createPartialMock(
            \Magento\SharedCatalog\Model\Form\Storage\Company::class,
            ['assignCompanies', 'isCompanyAssigned']
        );
        $this->loggerMock = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['critical']
        );

        $objectManager = new ObjectManager($this);
        $this->massAssignController = $objectManager->getObject(
            MassAssign::class,
            [
                'context' => $this->contextMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'filter' => $this->filterMock,
                'collectionFactory' => $this->companyCollectionFactoryMock,
                'companyStorageFactory' => $this->companyStorageFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test for method Execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $sameId = 12;
        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY],
                ['is_assign']
            )
            ->willReturn($sameId);
        $this->companyStorageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->companyStorageMock);
        $json = $this->createMock(Json::class);
        $this->companyCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->companyCollectionMock);
        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->companyCollectionMock)
            ->willReturn($this->companyCollectionMock);
        $this->companyCollectionMock->expects($this->once())->method('getItems')->willReturn([]);
        $this->companyStorageMock->expects($this->any())->method('assignCompanies')->willReturnSelf();
        $json->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($json);

        $this->massAssignController->execute();
    }
}
