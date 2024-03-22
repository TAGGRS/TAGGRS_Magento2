<?php

namespace Taggrs\DataLayer\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\DataLayer;
use Taggrs\DataLayer\Helper\UserDataHelper;

abstract class AbstractDataLayerController extends DataLayer implements HttpGetActionInterface
{
    protected Context $context;

    protected JsonFactory $resultFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultFactory,
        UserDataHelper $userDataHelper
    )
    {
        parent::__construct($userDataHelper);

        $this->context = $context;
        $this->resultFactory = $resultFactory;
    }


    public function execute(): ResultInterface
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $resultJson = $this->resultFactory->create();
        $dataLayer = $this->getDataLayer();
        ObjectManager::getInstance()->get(LoggerInterface::class)->critical(print_r($dataLayer, true));

        $resultJson->setData($this->getDataLayer());
        ObjectManager::getInstance()->get(LoggerInterface::class)->critical(print_r($dataLayer, true));

        return $resultJson;
    }
}
