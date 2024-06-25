<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

class NoScript extends Template
{
    public function getGtmUrl(): string
    {
        if ($gtmUrl = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_url')) {
            return $gtmUrl;
        }

        return 'www.googletagmanager.com';
    }

    public function getGtmCode(): ?string
    {
        return $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_code');
    }
}
