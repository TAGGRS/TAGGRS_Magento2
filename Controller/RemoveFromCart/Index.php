<?php

namespace Taggrs\DataLayer\Controller\RemoveFromCart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{
    private Session $checkoutSession;


    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        UserDataHelper $userDataHelper
    ) {
        parent::__construct($context, $resultJsonFactory, $userDataHelper, $storeManager);

        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
    }

    public function getEvent(): string
    {
        return 'remove_from_cart';
    }

    public function getEcommerce(): array
    {
        $quoteItemId = $this->context->getRequest()->getParam('id');
        $quote = $this->checkoutSession->getQuote();

        $ecommerce = [
            'items' => [],
            'currency' => $this->getCurrency()
        ];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {

            if ($quoteItem->getId() == $quoteItemId) {
                $ecommerce['value'] = floatval($quoteItem->getPriceInclTax()) * $quoteItem->getQty();
                $item = [];
                $item['item_id'] = $quoteItem->getProduct()->getId();
                $item['item_name'] = $quoteItem->getProduct()->getName();
                $item['item_category'] = implode(',', $quoteItem->getProduct()->getCategoryIds());
                $item['price'] = floatval($quoteItem->getPriceInclTax());
                $item['quantity'] = $quoteItem->getQty();

                $ecommerce['items'] = [$item];
                break;
            }
        }

        $ecommerce['user_data'] = $this->getUserData();

        return $ecommerce;
    }
}
