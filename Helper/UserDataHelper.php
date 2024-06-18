<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class UserDataHelper
{
    private CustomerSession $customerSession;

    private CheckoutSession $checkoutSession;


    public function __construct(CustomerSession $customerSession, CheckoutSession $checkoutSession)
    {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }


    public function getUserData(): array
    {
        $email = null;

        if ($this->customerSession->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();

        } elseif ($quote = @$this->checkoutSession->getQuote()) {
            $email = $quote->getBillingAddress()->getEmail();
        }

        if ($email !== null) {
            return [
                'email_hashed' => hash('sha256', $email),
                'email' => $email,
            ];
        }


        return [];
    }
}
