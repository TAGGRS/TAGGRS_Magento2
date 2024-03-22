<?php

namespace Taggrs\DataLayer\Block;

use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Api\DataLayerInterface;
use Taggrs\DataLayer\Helper\UserDataHelper;

abstract class DataLayer extends Template implements DataLayerInterface
{

    private UserDataHelper $userDataHelper;


    public $_template = 'data-layer.phtml';

    public function __construct(
        UserDataHelper $userDataHelper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->userDataHelper = $userDataHelper;
    }


    public function getDataLayerJson(): string
    {
        return json_encode($this->getDataLayer());
    }

    public function getDataLayer(): array
    {
        return [
            'event' => $this->getEvent(),
            'ecommerce' => $this->getEcommerce(),
            'user_data' => $this->getUserData(),
        ];
    }

    public function getUserData(): array
    {
        return $this->userDataHelper->getUserData();
    }
}
