<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Bookmark;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Form\Storage\CompanyFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\Component\Bookmark\Company;
use Magento\Ui\Api\BookmarkManagementInterface;
use Magento\Ui\Api\BookmarkRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UI  Component\Bookmark\Company.
 */
class CompanyTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Company
     */
    private $company;

    /**
     * @var ContextInterface|MockObject
     */
    private $context;

    /**
     * @var BookmarkRepositoryInterface|MockObject
     */
    private $bookmarkRepository;

    /**
     * @var BookmarkManagementInterface|MockObject
     */
    private $bookmarkManagement;

    /**
     * @var CompanyFactory|MockObject
     */
    private $companyStorageFactory;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $catalogManagement;

    /**
     * @var BookmarkManagementInterface|MockObject
     */
    private $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $processor = $this->getMockBuilder(Processor::class)
            ->setMethods(['register', 'notify'])
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects($this->exactly(1))->method('register');
        $processor->expects($this->exactly(1))->method('notify');

        $this->context = $this
            ->getMockBuilder(ContextInterface::class)
            ->setMethods(['getRequestParam', 'getProcessor', 'getNamespace', 'addComponentDefinition'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $configureKey = 'sadg346347sdf345';
        $mapForGetRequestParamMethod = [
            [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY, null, $configureKey]
        ];
        $this->context->expects($this->exactly(2))->method('getRequestParam')
            ->willReturnMap($mapForGetRequestParamMethod);
        $this->context->expects($this->exactly(2))->method('getProcessor')->willReturn($processor);
        $namespace = '';
        $this->context->expects($this->exactly(2))->method('getNamespace')->willReturn($namespace);
        $this->context->expects($this->exactly(2))->method('addComponentDefinition');

        $this->bookmarkRepository = $this
            ->getMockBuilder(BookmarkRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->bookmarkManagement = $this
            ->getMockBuilder(BookmarkManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storage = $this->getMockBuilder(BookmarkManagementInterface::class)
            ->setMethods(['getAssignedCompaniesIds'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyStorageFactory = $this
            ->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyStorageFactory->expects($this->exactly(1))->method('create')->willReturn($this->storage);

        $this->catalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->setMethods(['getPublicCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->company = $this->objectManagerHelper->getObject(
            Company::class,
            [
                'context' => $this->context,
                'bookmarkRepository' => $this->bookmarkRepository,
                'bookmarkManagement' => $this->bookmarkManagement,
                'companyStorageFactory' => $this->companyStorageFactory,
                'catalogManagement' => $this->catalogManagement
            ]
        );
    }

    /**
     * Test for prepare().
     *
     * @param array $assignedCompaniesIds
     * @param array $calls
     * @dataProvider prepareDataProvider
     * @return void
     */
    public function testPrepare(array $assignedCompaniesIds, array $calls)
    {
        $this->storage->expects($this->exactly(1))->method('getAssignedCompaniesIds')
            ->willReturn($assignedCompaniesIds);

        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogId = 235;
        $sharedCatalog->expects($this->exactly($calls['sharedCatalog_getId']))->method('getId')
            ->willReturn($sharedCatalogId);

        $this->catalogManagement->expects($this->exactly($calls['catalogManagement_getPublicCatalog']))
            ->method('getPublicCatalog')->willReturn($sharedCatalog);

        $this->company->prepare();
    }

    /**
     * Data provider for prepare() test.
     *
     * @return array
     */
    public function prepareDataProvider()
    {
        $companyId = 23;
        return [
            [
                [$companyId], ['catalogManagement_getPublicCatalog' => 0, 'sharedCatalog_getId' => 0]
            ],
            [
                [], ['catalogManagement_getPublicCatalog' => 1, 'sharedCatalog_getId' => 1]
            ]
        ];
    }
}
