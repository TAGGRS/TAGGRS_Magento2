<?php

namespace Taggrs\DataLayer\Block;

use Magento\Customer\Model\Session;
use Taggrs\DataLayer\Helper\UserDataHelper;
use Magento\Framework\View\Element\Template;


class SessionDataLayer extends DataLayer
{
    private Session $session;

    public function __construct(
        Session          $session,
        UserDataHelper   $userDataHelper,
        Template\Context $context,
        array            $data = []
    )
    {
        parent::__construct($userDataHelper, $context, $data);

        $this->session = $session;
    }

    public function getDataLayer(): array
    {
        if ($this->session->getDataLayer()) {
            $dataLayer = $this->session->getDataLayer();
            $this->session->unsDataLayer();
            return $dataLayer;
        }

        return [];
    }

    public function getDataLayerJson(): string
    {
        return json_encode($this->getDataLayer());
    }

    public function getEvent(): string
    {
        return '';
    }

    public function getEcommerce(): array
    {
        return [];
    }


}
