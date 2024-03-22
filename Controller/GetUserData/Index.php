<?php

namespace Taggrs\DataLayer\Controller\GetUserData;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index implements HttpGetActionInterface
{
    private UserDataHelper $userDataHelper;

    private JsonFactory $jsonFactory;

    /**
     * @param UserDataHelper $quoteDataHelper
     * @param JsonFactory $jsonFactory
     */
    public function __construct(UserDataHelper $userDataHelper, JsonFactory $jsonFactory)
    {
        $this->userDataHelper = $userDataHelper;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $userData = $this->userDataHelper->getUserData();
        $result = $this->jsonFactory->create();
        $result->setData($userData);
        return  $result;
    }
}
