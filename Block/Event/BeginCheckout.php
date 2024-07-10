<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Generates a Data Layer for the begin_checkout event on the Checkout Index Page
 */
class BeginCheckout extends DataLayer
{

    /**
     * @var QuoteDataHelper to retrieve data from the current quote
     */
    private QuoteDataHelper $quoteDataHelper;

    /**
     * Class constructor
     *
     * @param QuoteDataHelper $quoteDataHelper
     * @param UserDataHelper $userDataHelper
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->quoteDataHelper   = $quoteDataHelper;
    }

    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string
    {
        return 'begin_checkout';
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
