<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Purchase extends DataLayer
{
    private CheckoutSession $checkoutSession;

    private ProductViewDataHelper $productHelper;


    public function __construct(
        CheckoutSession       $checkoutSession,
        ProductViewDataHelper $productHelper,
        UserDataHelper        $userDataHelper,
        Template\Context      $context,
        array                 $data = []

    )
    {
        parent::__construct($userDataHelper, $context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->productHelper   = $productHelper;
    }


    public function getEvent(): string
    {
        return 'purchase';
    }

    public function getEcommerce(): array
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return [
            'transaction_id' => $order->getIncrementId(),
            'currency' => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
            'value' => (float)$order->getGrandTotal(),
            'tax' => (float)$order->getTaxAmount(),
            'shipping' => (float)$order->getShippingAmount(),
//            'coupon' => $order->getCouponCode(),
            'items' => $this->productHelper->getItemsFromOrder($order),
            'user_data' => $this->getUserData()
        ];
    }

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
