<?php

namespace Taggrs\DataLayer;

use Taggrs\DataLayer\Api\DataLayerInterface;
use Taggrs\DataLayer\Helper\UserDataHelper;

abstract class DataLayer implements DataLayerInterface
{
    private UserDataHelper $userDataHelper;

    /**
     * @param UserDataHelper $userDataHelper
     */
    public function __construct(UserDataHelper $userDataHelper)
    {
        $this->userDataHelper = $userDataHelper;
    }

    public function getUserData(): array
    {
        return $this->userDataHelper->getUserData();
    }

    public function getDataLayer(): array
    {
        return [
            'event' => $this->getEvent(),
            'ecommerce' => $this->getEcommerce(),
        ];
    }
}
