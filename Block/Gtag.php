<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

/**
 * Renders the Google Tag Manager loading script
 */
class Gtag extends Template
{
    /**
     * @var QuoteDataHelper to retrieve data from current customer quote
     */
    private QuoteDataHelper $quoteDataHelper;

    /**
     * Class constructor
     *
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

    /**
     * Get the Google Tag Manager code
     *
     * @return string|null
     */
    public function getGtmCode(): ?string
    {
        $gtmCode = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_code', ScopeInterface::SCOPE_STORE);

        return is_string($gtmCode) ? trim($gtmCode) : null;
    }

    /**
     * Get the Google Tag Manager URL
     *
     * @return string
     */
    public function getGtmUrl(): string
    {
        if ($gtmUrl = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_url', ScopeInterface::SCOPE_STORE)) {
            return $gtmUrl;
        }

        return 'www.googletagmanager.com';
    }

    /**
     * Checks if the debug mode for the Data Layer push is enabled
     *
     * @return bool whether the debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return (bool)$this->_scopeConfig->getValue('taggrs_datalayer/gtm/debug_mode', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the current customer's quote data
     *
     * @return string
     */
    public function getQuoteData(): string
    {
        return json_encode($this->quoteDataHelper->getQuoteData());
    }

    /**
     * Get the store's currency code
     *
     * @return string
     */
    public function getCurrency(): ?string
    {
        try {
            return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch ( NoSuchEntityException|LocalizedException $e ) {
            return null;
        }
    }

    /**
     * Get the configuration for the events, if they are enabled or not
     *
     * @return string
     */
    public function getAjaxEventsConfig(): string
    {
        return json_encode([
             'remove_from_cart' => (bool)$this->_scopeConfig->getValue('taggrs_datalayer/events/remove_from_cart', ScopeInterface::SCOPE_STORE),
        ]);
    }
}
