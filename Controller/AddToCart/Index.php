<?php

namespace Taggrs\DataLayer\Controller\AddToCart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{

    private Session $checkoutSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        UserDataHelper $userDataHelper
    )
    {
        parent::__construct($context, $resultJsonFactory, $userDataHelper);

        $this->checkoutSession = $checkoutSession;
    }

    public function getEvent(): string
    {
        return 'add_to_cart';
    }

    public function getEcommerce(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
        }

        if (!isset($quote)) {
            return [];
        }

        $max = 0;
        $lastItem = null;

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getId() > $max) {
                $max = $quoteItem->getId();
                $lastItem = $quoteItem;
            }
        }

        if ($lastItem === null) {
            return [];
        }

        return [
            'items' => [[
                'item_id' => $lastItem->getProduct()->getId(),
                'item_name' => $lastItem->getProduct()->getName(),
                'price' => $lastItem->getPriceInclTax(),
            ]]
        ];
    }
}
