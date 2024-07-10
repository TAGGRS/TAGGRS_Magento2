<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Api\DataLayerInterface;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Use this class to create view blocks for generating a DataLayer
 */
abstract class DataLayer extends Template implements DataLayerInterface
{

    /**
     * @var UserDataHelper to get User Data from the session
     */
    private UserDataHelper $userDataHelper;

    /**
     * @var string block template
     */
    public $_template = 'data-layer.phtml';

    /**
     * Class constructor
     *
     * @param UserDataHelper $userDataHelper
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        UserDataHelper $userDataHelper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->userDataHelper = $userDataHelper;
    }

    /**
     * Get the Google Tag Manager URL from the system configuration
     *
     * @return string
     */
    public function getGtmUrl(): string
    {
        if ($gtmUrl = $this->_scopeConfig->getValue('taggrs_datalayer/gtm/gtm_url')) {
            return $gtmUrl;
        }

        return 'www.googletagmanager.com';
    }

    /**
     * Get JSON-encoded Data Layer
     *
     * @return string
     */
    public function getDataLayerJson(): string
    {
        return json_encode($this->getDataLayer());
    }

    /**
     * Get Data Layer as associative array
     *
     * @return array
     */
    public function getDataLayer(): array
    {
        return [
            'event' => $this->getEvent(),
            'ecommerce' => $this->getEcommerce(),
        ];
    }

    /**
     * Get user data from session
     *
     * @return array
     */
    public function getUserData(): array
    {
        return $this->userDataHelper->getUserData();
    }
}
