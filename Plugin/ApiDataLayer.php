<?php

namespace Taggrs\DataLayer\Plugin;

use AlexWestergaard\PhpGa4\Analytics;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Taggrs\DataLayer\Api\DataLayerInterface;

abstract class ApiDataLayer implements DataLayerInterface
{
    private ScopeConfigInterface $config;

    private Curl $curl;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ScopeConfigInterface $config,
        Curl $curl
    )
    {
        $this->config = $config;
        $this->curl = $curl;
    }

    protected function doRequest(): void
    {
        $measurementId = $this->config->getValue('taggrs_datalayer/gtm/gtm_code');
        $apiSecret = $this->config->getValue('taggrs_datalayer/gtm/api_secret');

        $body = [
            ''
        ];
    }
}
