<?php

namespace Taggrs\DataLayer\Controller\AddShippingInfo;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{
    private QuoteDataHelper $quoteDataHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper $userDataHelper,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context, $resultJsonFactory, $userDataHelper, $storeManager);

        $this->quoteDataHelper = $quoteDataHelper;
        $this->storeManager = $storeManager;
    }

    public function getEvent(): string
    {
        return 'add_shipping_info';
    }

    public function getEcommerce(): array
    {

        $total = (float)$this->quoteDataHelper->getQuote()->getGrandTotal();

        return [
            'currency' => $this->getCurrency(),
            'value' => $total,
            'items' => $this->quoteDataHelper->getItemsFromQuote(true, true),
            'user_data' => $this->getUserData(),
        ];
    }
}
