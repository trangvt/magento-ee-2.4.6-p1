<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Configure;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder as StorageUrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Configure\TierPrice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento/SharedCatalog/Ui/Component/Listing/Column/Configure/TierPrice class.
 */
class TierPriceTest extends TestCase
{
    /**
     * @var TierPrice
     */
    private $column;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $context = $this->getMockBuilder(ContextInterface::class)
            ->setMethods(['getProcessor'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wizardStorageFactory = $this->getMockBuilder(
            WizardFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(
            RequestInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->setMethods(['register', 'notify'])
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('getProcessor')
            ->willReturn($processor);

        $attribute = $this->getMockBuilder(ProductAttributeInterface::class)
            ->getMock();
        $attribute->expects($this->once())
            ->method('getApplyTo')
            ->willReturn(['simple', 'virtual', 'bundle', 'downloadable']);
        $attributeRepository = $this->getMockBuilder(ProductAttributeRepositoryInterface::class)
            ->getMock();
        $attributeRepository->expects($this->once())->method('get')->with('tier_price')->willReturn($attribute);

        $this->column = $objectManager->getObject(
            TierPrice::class,
            [
                'context' => $context,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'request' => $this->request,
                'attributeRepository' => $attributeRepository,
            ]
        );

        $this->column->setData('name', 'tier_price');
    }

    /**
     * Test prepareDataSource method.
     *
     * @param string $type
     * @param boolean $isAllowed
     * @return void
     * @dataProvider prepareSourceDataProvider
     */
    public function testPrepareDataSource($type, $isAllowed)
    {
        $dataSource['data'] = [
            'items' => [
                [
                    'type_id' => $type,
                    'entity_id' => 1,
                    'tier_price_count' => 8
                ]
            ]
        ];
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())
            ->method('getParam')
            ->with(StorageUrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
            ->willReturn('configure_key');
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($storage);
        $storage->expects($this->once())->method('getStoreId')->willReturn(1);
        $dataSource = $this->column->prepareDataSource($dataSource);
        if ($isAllowed) {
            $this->assertArrayHasKey('tier_price', $dataSource['data']['items'][0]);
        } else {
            $this->assertArrayNotHasKey('tier_price', $dataSource['data']['items'][0]);
        }
    }

    /**
     * Data provider for prepareDataSource method.
     *
     * @return array
     */
    public function prepareSourceDataProvider()
    {
        return [
            ['virtual', true],
            ['simple', true],
            ['configurable', false],
            ['bundle', true],
            ['grouped', false],
            ['giftcard', false]
        ];
    }
}
