<?php

namespace Taggrs\DataLayer\Observer;

use GuzzleHttp\Client;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\Event\Purchase;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class PurchaseEvent implements ObserverInterface
{
    private ScopeConfigInterface $config;

    private UserDataHelper $userDataHelper;

    private ProductViewDataHelper $productViewDataHelper;

    private CookieManagerInterface $cookieManager;

    private Client $client;

    private StoreManagerInterface $storeManager;

    private Session $checkoutSession;

    /**
     * @param ScopeConfigInterface $config
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        ScopeConfigInterface $config,
        CookieManagerInterface $cookieManager,
        UserDataHelper $userDataHelper,
        ProductViewDataHelper $productViewDataHelper,
        StoreManagerInterface $storeManager,
        Client $client,
        Session $checkoutSession
    ) {
        $this->config        = $config;
        $this->cookieManager = $cookieManager;
        $this->userDataHelper = $userDataHelper;
        $this->productViewDataHelper = $productViewDataHelper;
        $this->client         = $client;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute( Observer $observer )
    {
        $logger = ObjectManager::getInstance()->get(LoggerInterface::class);

        $eventEnabled = $this->config->getValue('taggrs_datalayer/events/purchase_via_measurement_api');

        $logger->critical("Purchase event enabled: " . $eventEnabled);

        if (!$eventEnabled) {
            return;
        }

        $measurementId = $this->config->getValue('taggrs_datalayer/gtm/api_measurement_id');
        $apiSecret = $this->config->getValue('taggrs_datalayer/gtm/api_secret');


        $url = 'https://www.google-analytics.com/mp/collect';
        $url .= '?' . http_build_query(['measurement_id' => $measurementId, 'api_secret' => $apiSecret]);

        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        $body = [
            'client_id' => $this->cookieManager->getCookie('_ga'),
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'ecommerce' => [
                            'transaction_id' => $order->getIncrementId(),
                            'currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                            'value' => (float)$order->getGrandTotal(),
                            'tax' => (float)$order->getTaxAmount(),
                            'shipping' => (float)$order->getShippingAmount(),
//                            'coupon' => $order->getCouponCode(),
                            'items' => $this->productViewDataHelper->getItemsFromOrder($order),
                            'user_data' => $this->getUserData()
                        ]
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($body);
        $jsonBody = strtr($jsonBody, [':[]' => ':{}']);

        $logger->critical($jsonBody);

        $res = $this->client->request('POST', $url, [
            'headers' => [
                'content-type' => 'application/json;charset=utf-8'
            ],
            'body' => $jsonBody,
        ]);

        $logger->critical($res->getStatusCode());


        // TODO: Implement execute() method.
    }

    private function getUserData(): array
    {
        $userData = $this->userDataHelper->getUserData();

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
