<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class BeginCheckout extends DataLayer
{

    private QuoteDataHelper $quoteDataHelper;

    public function __construct(
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->quoteDataHelper   = $quoteDataHelper;
    }


    public function getEvent(): string
    {
        return 'begin_checkout';
    }

    public function getEcommerce(): array
    {
        $currency = $this->_storeManager
            ->getStore()
            ->getCurrentCurrency()
            ->getCode()
        ;

        $total = (float)$this->quoteDataHelper->getQuote()->getGrandTotal();
        $items = $this->quoteDataHelper->getItemsFromQuote();

        return [
            'currency' => $currency,
            'value' => $total,
            'items' => $items,
            'user_data' => $this->getUserData(),
        ];
    }
}
