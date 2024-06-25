<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

class Gtag extends Template
{
    private QuoteDataHelper $quoteDataHelper;

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param QuoteDataHelper $quoteDataHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        QuoteDataHelper $quoteDataHelper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteDataHelper = $quoteDataHelper;
    }


    public function getGtmCode(): ?string
    {
        return $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_code');
    }

    public function getGtmUrl(): string
    {
        if ($gtmUrl = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_url')) {
            return $gtmUrl;
        }

        return 'www.googletagmanager.com';
    }

    public function isDebugMode(): bool
    {
        return (bool)$this->_scopeConfig->getValue('taggrs_datalayer/gtm/debug_mode');
    }

    public function getQuoteData(): string
    {
        return json_encode($this->quoteDataHelper->getQuoteData());
    }

    public function getCurrency(): string
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getAjaxEventsConfig(): string
    {
        return json_encode([
             'remove_from_cart' => (bool)$this->_scopeConfig->getValue('taggrs_datalayer/events/remove_from_cart'),
        ]);
    }
}
