<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Controller\Adminhtml;

use Magento\NegotiableQuote\Controller\Adminhtml\Quote\View;

/**
 * @magentoDataFixture Magento/NegotiableQuote/_files/negotiable_quote_with_company_and_customer.php
 * @magentoDbIsolation enabled
 * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
 * @magentoAppArea adminhtml
 */
class ViewTest extends AbstractTest
{
    /**
     * @var string
     */
    private $uriTemplate = 'backend/quotes/quote/view/quote_id/%s';

    /**
     * @inheritDoc
     */
    protected $resource = View::ADMIN_RESOURCE;

    /**
     * @inheritDoc
     */
    public function testAclHasAccess()
    {
        $this->uri = $this->interpolateUriWithQuoteId($this->uriTemplate);
        parent::testAclHasAccess();
    }

    /**
     * @inheritDoc
     */
    public function testAclNoAccess()
    {
        $this->uri = $this->interpolateUriWithQuoteId($this->uriTemplate);
        parent::testAclNoAccess();
    }
}
