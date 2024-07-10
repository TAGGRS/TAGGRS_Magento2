<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

/**
 * Renders the 'noscript' iframe for Google Tag Manager
 */
class NoScript extends Template
{
    /**
     * Get the Google Tag Manager URL
     *
     * @return string the Google Tag Manager URL
     */
    public function getGtmUrl(): string
    {
        if ($gtmUrl = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_url')) {
            return $gtmUrl;
        }

        return 'www.googletagmanager.com';
    }

    /**
     * Get the Google Tag Manager code
     *
     * @return string|null
     */
    public function getGtmCode(): ?string
    {
        $gtmCode = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_code');

        return is_string($gtmCode) ? trim($gtmCode) : null;
    }
}
