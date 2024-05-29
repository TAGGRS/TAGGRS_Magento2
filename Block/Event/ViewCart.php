<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class ViewCart extends DataLayer
{
    private CheckoutSession $checkoutSession;

    private QuoteDataHelper $quoteDataHelper;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductViewDataHelper $productHelper
     */
    public function __construct(
        CheckoutSession       $checkoutSession,
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []

    )
    {
        parent::__construct($userDataHelper, $context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->quoteDataHelper   = $quoteDataHelper;
    }


    public function getEvent(): string
    {
        return 'view_cart';
    }

    public function getEcommerce(): array
    {
        $currency = $this->_storeManager
            ->getStore()
            ->getCurrentCurrency()
            ->getCode()
        ;

        $items = $this->quoteDataHelper->getItemsFromQuote();

        return [
            'currency' => $currency,
            'value' => (float)$this->checkoutSession->getQuote()->getGrandTotal(),
            'coupon' => $this->quoteDataHelper->getQuote()->getCouponCode() ?? null,
            'items' => $items,
            'user_data' => $this->getUserData()
        ];
    }

}
