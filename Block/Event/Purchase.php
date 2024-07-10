<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Generates a Data Layer for the purchase event on the Checkout Success Page
 */
class Purchase extends DataLayer
{
    /**
     * @var CheckoutSession to retrieve data from the current quote
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var ProductViewDataHelper to help retrieve product information
     */
    private ProductViewDataHelper $productHelper;

    /**
     * Class constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param ProductViewDataHelper $productHelper
     * @param UserDataHelper $userDataHelper
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        CheckoutSession       $checkoutSession,
        ProductViewDataHelper $productHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->productHelper   = $productHelper;
    }

    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string
    {
        return 'purchase';
    }

    /**
     * Get the e-commerce Data Layer
     *
     * @return array containing the e-commerce data
     */
    public function getEcommerce(): array
    {
        $order = $this->checkoutSession->getLastRealOrder();
        try {
            $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch ( NoSuchEntityException|LocalizedException $e ) {
            $currency = null;
        }

        return [
            'transaction_id' => $order->getIncrementId(),
            'currency' => $currency,
            'value' => (float)$order->getGrandTotal(),
            'tax' => (float)$order->getTaxAmount(),
            'shipping' => (float)$order->getShippingAmount(),
            'items' => $this->productHelper->getItemsFromOrder($order),
            'user_data' => $this->getUserData()
        ];
    }

    /**
     * Get the user_data Data Layer
     *
     * @return array containing the user data
     */
    public function getUserData(): array
    {
        $userData = parent::getUserData();

        $order = $this->checkoutSession->getLastRealOrder();
        $billingAddress = $order->getBillingAddress();

        $userData['email'] = $billingAddress->getEmail();
        $userData['email_hashed'] = hash('sha256', $billingAddress->getEmail());

        $userData['phone'] = $billingAddress->getTelephone();
        $userData['phone_hashed'] = hash('sha256', $billingAddress->getTelephone());

        $address = [];

        $address['first_name'] = $billingAddress->getFirstname();
        $address['last_name'] = $billingAddress->getLastname();
        $street = $billingAddress->getStreet();

        $address['street'] = implode(' ', $street);

        $address['city'] = $billingAddress->getCity();
        $address['postcode'] = $billingAddress->getPostcode();
        $address['country'] = $billingAddress->getCountryId();

        $userData['address'] = $address;

        return $userData;
    }
}
