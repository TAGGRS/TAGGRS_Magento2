<?php

namespace Taggrs\DataLayer\Controller\AddToCart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{

    private Session $checkoutSession;

    private QuoteDataHelper $quoteDataHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        UserDataHelper $userDataHelper,
        StoreManagerInterface $storeManager,
        QuoteDataHelper $quoteDataHelper
    )
    {
        parent::__construct($context, $resultJsonFactory, $userDataHelper, $storeManager);

        $this->checkoutSession = $checkoutSession;
        $this->quoteDataHelper = $quoteDataHelper;
    }

    public function getEvent(): string
    {
        return 'add_to_cart';
    }

    public function getEcommerce(): array
    {

        return [
            'currency' => $this->getCurrency(),
            'value' => (float)$this->checkoutSession->getQuote()->getGrandTotal(),
            'items' => $this->quoteDataHelper->getItemsFromQuote(),
            'user_data' => $this->getUserData(),
        ];
    }
}
