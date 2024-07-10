<?php

namespace Taggrs\DataLayer;

use Taggrs\DataLayer\Api\DataLayerInterface;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Extend this class to create child classes to generate specific Data Layer events
 */
abstract class DataLayer implements DataLayerInterface
{
    /**
     * @var UserDataHelper to retrieve customer data
     */
    private UserDataHelper $userDataHelper;

    /**
     * Class constructor
     *
     * @param UserDataHelper $userDataHelper
     */
    public function __construct(UserDataHelper $userDataHelper)
    {
        $this->userDataHelper = $userDataHelper;
    }

    /**
     * Get the user_data Data Layer.
     * This is the same for all events except 'purchase' so in most cases, don't override this method.
     *
     * @return array containing the user data
     */
    public function getUserData(): array
    {
        return $this->userDataHelper->getUserData();
    }

    /**
     * Get the Data Layer as an associative array
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
}
