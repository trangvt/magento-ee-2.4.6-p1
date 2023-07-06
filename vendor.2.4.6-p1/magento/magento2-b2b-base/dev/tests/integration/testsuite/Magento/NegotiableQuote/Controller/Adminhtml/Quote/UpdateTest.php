<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation\Rate as TaxRate;
use Magento\Tax\Model\Config;

/**
 * Tests quote update.
 *
 * @magentoAppArea adminhtml
 */
class UpdateTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var TaxRate
     */
    private $taxRate;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->taxRate = $this->_objectManager->create(TaxRate::class);
        $this->customerRepository = $this->_objectManager->create(CustomerRepositoryInterface::class);
        $this->negotiableQuoteRepository = $this->_objectManager->get(NegotiableQuoteRepositoryInterface::class);
        $this->negotiableQuoteManagement = $this->_objectManager->get(NegotiableQuoteManagementInterface::class);
        $this->productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);
        $this->mutableScopeConfig = $this->_objectManager->create(MutableScopeConfigInterface::class);
    }

    /**
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     * @magentoConfigFixture default_store btob/website_configuration/company_active true
     * @magentoDataFixture Magento/NegotiableQuote/_files/two_simple_products_for_quote.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/negotiable_quote.php
     *
     * @return void
     */
    public function testUpdateQuote(): void
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->_objectManager->create(CustomerRepositoryInterface::class);
        /** @var NegotiableQuoteRepositoryInterface $negotiableRepository */
        $negotiableRepository = $this->_objectManager->get(NegotiableQuoteRepositoryInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);

        $customer = $customerRepository->get('email@companyquote.com');
        $quotes = $negotiableRepository->getListByCustomerId($customer->getId());

        $quoteId = end($quotes)->getId();

        $postData = [
            'quote_id' => $quoteId,
            'quote' => [
                'items' => [
                    0 => [
                        'id' => $productRepository->get('simple')->getId(),
                        'qty' => '1',
                        'sku' => 'simple',
                        'productSku' => 'simple',
                        'config' => '',
                    ],
                ],
                'addItems' => [
                    0 => [
                        'qty' => '1',
                        'sku' => 'simple_for_quote',
                    ],
                ],
                'update' => 1,
                'recalcPrice' => 1,
            ],
        ];

        $this->getRequest()->setPostValue($postData)->setMethod('POST');
        $this->dispatch('backend/quotes/quote/update/?isAjax=true');

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $quote = $quoteRepository->get($quoteId);
        /** @var  QuoteUpdatesInfo $quoteInfo */
        $quoteInfo = $this->_objectManager->create(QuoteUpdatesInfo::class);
        $updatedData = $quoteInfo->getQuoteUpdatedData($quote, $postData);

        foreach ($updatedData['items'] as $item) {
            $this->assertUpdatedItemsData($item);
        }
    }

    /**
     * @magentoDataFixture Magento/NegotiableQuote/_files/product_simple_taxable.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/negotiable_quote_with_shipping_address.php
     *
     * @magentoDataFixture Magento/NegotiableQuote/_files/tax_rule_us.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/tax_rule_de.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     *
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     * @magentoConfigFixture default_store btob/website_configuration/company_active true
     *
     * @magentoConfigFixture current_store general/country/default DE
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 82
     * @magentoConfigFixture current_store shipping/origin/postcode 10115
     *
     * @dataProvider taxRateDataProvider
     *
     * @param string $taxCalculationType
     * @param bool $isCatalogPriceIncludeTax
     * @param float $taxRateStore
     * @param float $taxRateCustomer
     * @param float $proposedPrice
     * @param bool $isQuoteSentToAdminFirstBeforeProposingPrice
     * @return void
     */
    public function testPriceCalculationWithDifferentTaxRateOnUpdateQuote(
        string $taxCalculationType,
        bool $isCatalogPriceIncludeTax,
        float $taxRateStore,
        float $taxRateCustomer,
        float $proposedPrice,
        bool $isQuoteSentToAdminFirstBeforeProposingPrice,
        bool $isCrossBorderTradeEnabled
    ): void {
        // Set Tax calculation basis (either shipping or billing)
        $this->mutableScopeConfig->setValue(
            'tax/calculation/based_on',
            $taxCalculationType,
            ScopeInterface::SCOPE_STORE
        );
        $this->mutableScopeConfig->setValue(
            'tax/calculation/based_on',
            $taxCalculationType,
            ScopeInterface::SCOPE_STORE
        );
        $this->mutableScopeConfig->setValue(
            Config::CONFIG_XML_PATH_CROSS_BORDER_TRADE_ENABLED,
            $isCrossBorderTradeEnabled,
            ScopeInterface::SCOPE_STORE
        );

        // Set whether catalog prices include tax (either true or false)
        $this->mutableScopeConfig->setValue(
            'tax/calculation/price_includes_tax',
            $isCatalogPriceIncludeTax,
            ScopeInterface::SCOPE_STORE
        );

        // Get DE (Where the store is based) Tax Rate and assign $taxRateStore
        $fixtureTaxRateDE = $this->taxRate->load('Test Rate DE', 'code');

        $fixtureTaxRateDE
            ->setRate($taxRateStore)
            ->save();

        // Get US (Where the customer is based) Tax Rate and assign $taxRateCustomer
        $fixtureTaxRateUS = $this->taxRate->load('Test Rate US', 'code');

        $fixtureTaxRateUS
            ->setRate($taxRateCustomer)
            ->save();

        $customer = $this->customerRepository->get('email@companyquote.com');
        $quotes = $this->negotiableQuoteRepository->getListByCustomerId($customer->getId());
        $quoteId = end($quotes)->getId();

        if ($isQuoteSentToAdminFirstBeforeProposingPrice) {
            $this->negotiableQuoteManagement->send($quoteId);
        }

        $postData = [
            'quote_id' => $quoteId,
            'quote' => [
                'items' => [
                    0 => [
                        'id' => $this->productRepository->get('simple')->getId(),
                        'qty' => '1',
                        'sku' => 'simple',
                        'productSku' => 'simple',
                        'config' => '',
                    ],
                ],
                'proposed' => [
                    'type' => NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL,
                    'value' => $proposedPrice,
                ],
                'update' => 0
            ],
            'negotiable_quote_update_flag' => true,
        ];

        $this->getRequest()->setPostValue($postData)->setMethod('POST');
        $this->dispatch('backend/quotes/quote/update/?isAjax=true');

        $response = $this->getResponse();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($proposedPrice, (float) trim($responseContent['quoteSubtotal']['base'], '$'));
        $this->assertEquals($proposedPrice, (float) trim($responseContent['items'][0]['proposedPrice'], '$'));

        $this->assertEquals(
            round($proposedPrice * ((100 + $taxRateCustomer) / 100), PriceCurrencyInterface::DEFAULT_PRECISION),
            (float) trim($responseContent['grandTotal']['base'], '$')
        );
    }

    /**
     * Data provider for testPriceCalculationWithDifferentTaxRateOnUpdateQuote
     *
     * @return array
     */
    public function taxRateDataProvider(): array
    {
        return [
            ['shipping', true, 20.0, 0.0, 2.00, true, false],
            ['shipping', false, 21.0, 10.0, 2.00, true, false],
            ['shipping', true, 21.0, 10.0, 2.00, false, false],
            ['shipping', false, 21.0, 10.0, 2.00, false, false],
            ['shipping', false, 10.0, 25.5, 2.11, false, false],
            ['shipping', false, 0, 10.0, 5.00, false, false],
            ['shipping', false, 10.0, 0, 5.00, false, false],
            ['shipping', false, 0, 0, 10.00, false, false],
            ['shipping', false, 10.0, 10.0, 20.00, false, false],
            ['billing', true, 21.0, 10.0, 2.00, true, false],
            ['billing', false, 21.0, 10.0, 2.00, true, false],
            ['billing', true, 21.0, 10.0, 2.00, false, false],
            ['billing', false, 21.0, 10.0, 2.00, false, false],
            ['shipping', true, 20.0, 10.0, 2.00, true, true],
            ['billing', true, 10.0, 20.0, 2.00, true, true],
        ];
    }

    /**
     * Assert updated quote items data.
     *
     * @param $item
     * @return void
     */
    private function assertUpdatedItemsData($item): void
    {
        if ($item['sku'] === 'simple_for_quote') {
            $this->assertEquals('$16.00', $item['subtotal']);
            $this->assertEquals('$20.00', $item['cartPrice']);
            $this->assertEquals('$20.00', $item['originalPrice']);
            $this->assertEquals('$16.00', $item['proposedPrice']);
        } elseif ($item['sku'] === 'simple') {
            $this->assertEquals('$8.00', $item['subtotal']);
            $this->assertEquals('$10.00', $item['cartPrice']);
            $this->assertEquals('$10.00', $item['originalPrice']);
            $this->assertEquals('$8.00', $item['proposedPrice']);
        }
    }
}
