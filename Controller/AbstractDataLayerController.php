<?php

namespace Taggrs\DataLayer\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\DataLayer;
use Taggrs\DataLayer\Helper\UserDataHelper;

abstract class AbstractDataLayerController extends DataLayer implements HttpGetActionInterface
{
    protected Context $context;

    protected JsonFactory $resultFactory;

    protected StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultFactory,
        UserDataHelper $userDataHelper,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($userDataHelper);

        $this->context = $context;
        $this->resultFactory = $resultFactory;
        $this->storeManager = $storeManager;
    }


    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create();
//        $dataLayer = $this->getDataLayer();

        $resultJson->setData($this->getDataLayer());
//        ObjectManager::getInstance()->get(LoggerInterface::class)->critical(print_r($dataLayer, true));

        return $resultJson;
    }

    protected function getCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }
}
