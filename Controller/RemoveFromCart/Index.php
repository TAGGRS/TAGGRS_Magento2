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

    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        UserDataHelper $userDataHelper
    )
    {
        parent::__construct($context, $resultJsonFactory, $userDataHelper);

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
        ObjectManager::getInstance()->get(LoggerInterface::class)->critical('quote item id remove: ' . $quoteItemId);
        $quote = $this->checkoutSession->getQuote();

        $ecommerce = [
            'items' => [],
            'currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode()
        ];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getId() == $quoteItemId) {
                $ecommerce['value'] = floatval($quoteItem->getPriceInclTax());;
                $item = [];
                $item['item_id'] = $quoteItem->getProduct()->getId();
                $item['item_name'] = $quoteItem->getProduct()->getName();
                $item['item_category'] = implode(',', $quoteItem->getProduct()->getCategoryIds());
                $item['price'] = floatval($quoteItem->getPriceInclTax());
                $item['quantity'] = $quoteItem->getQty();
                $ecommerce['items'] = $item;
                break;
            }
        }
        return $ecommerce;
    }
}
