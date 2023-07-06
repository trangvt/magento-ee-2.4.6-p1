<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PaypalNegotiableQuote\Plugin\Payment\Block\Transparent;

use Magento\Framework\App\RequestInterface;
use Magento\Payment\Block\Transparent\Redirect;

class RedirectPlugin
{
    /**
     * Route path param
     */
    private $routePathParam = 'route_path';

    /**
     * @var string
     */
    private $negotiableQuoteIdParam = 'negotiableQuoteId';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * Add negotiable quote id param to url
     *
     * @param Redirect $subject
     * @param string $result
     */
    public function beforeGetRedirectUrl(Redirect $subject)
    {
        $negotiableQuoteId = $this->request->getParam($this->negotiableQuoteIdParam);
        $routePathRedirect = $subject->getData($this->routePathParam);
        if ($negotiableQuoteId && $routePathRedirect) {
            $routePathRedirect = rtrim($routePathRedirect, '/');
            $routePathRedirect .= '/' . $this->negotiableQuoteIdParam . '/' . $negotiableQuoteId;
            $subject->setData($this->routePathParam, $routePathRedirect);
        }
    }
}
