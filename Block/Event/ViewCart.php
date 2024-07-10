<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Generates Data Layer for the view_cart event on the Checkout Cart page
 */
class ViewCart extends DataLayer
{
    /**
     * @var CheckoutSession to retrieve data from the quote
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var QuoteDataHelper to help retrieve data from the quote
     */
    private QuoteDataHelper $quoteDataHelper;

    /**
     * Class constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param QuoteDataHelper $quoteDataHelper
     * @param UserDataHelper $userDataHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        CheckoutSession       $checkoutSession,
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->quoteDataHelper   = $quoteDataHelper;
    }

    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string
    {
        return 'view_cart';
    }

    /**
     * Get the e-commerce Data Layer
     *
     * @return array containing the e-commerce data
     */
    public function getEcommerce(): array
    {
        try {
            $currency = $this->_storeManager
                ->getStore()
                ->getCurrentCurrency()
                ->getCode()
            ;
        } catch ( NoSuchEntityException|LocalizedException $e ) {
            $currency = null;
        }

        $items = $this->quoteDataHelper->getItemsFromQuote();

        try {
            $value = (float) $this->checkoutSession->getQuote()->getGrandTotal();
        } catch ( NoSuchEntityException|LocalizedException $e ) {
            $value = 0;
        }

        return [
            'currency' => $currency,
            'value' => $value,
            'coupon' => $this->quoteDataHelper->getQuote()->getCouponCode() ?? null,
            'items' => $items,
            'user_data' => $this->getUserData()
        ];
    }
}
