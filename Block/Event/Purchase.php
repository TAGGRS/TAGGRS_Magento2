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
            'value' => $order->getGrandTotal(),
            'tax' => $order->getTaxAmount(),
            'shipping' => $order->getShippingAmount(),
            'coupon' => $order->getCouponCode(),
            'items' => $this->productHelper->getItemsFromOrder($order),
        ];
    }

    public function getUserData(): array
    {
        $userData = parent::getUserData();

        $order = $this->checkoutSession->getLastRealOrder();
        $billingAddress = $order->getBillingAddress();

        $userData['email'] = $billingAddress->getEmail();
        $userData['email_hashed'] = hash('sha256', $billingAddress->getEmail());

        $userData['first_name'] = $billingAddress->getFirstname();
        $userData['last_name'] = $billingAddress->getLastname();

        $street = $billingAddress->getStreet();

        if (isset($street[0])) {
            $userData['address_1'] = $street[0];
        }
        if (isset($street[0])) {
            $userData['address_2'] = $street[1];
        }

        $userData['city'] = $billingAddress->getCity();
        $userData['postcode'] = $billingAddress->getPostcode();
        $userData['country'] = $billingAddress->getCountryId();
        $userData['phone'] = $billingAddress->getTelephone();
        $userData['phone_hashed'] = hash('sha256', $billingAddress->getTelephone());

        return $userData;
    }
}
