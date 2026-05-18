<?php

namespace Taggrs\DataLayer\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Taggrs\DataLayer\DataLayer;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class AddToCart extends DataLayer implements ObserverInterface
{
    private CheckoutSession $checkoutSession;

    private CustomerSession $customerSession;

    private QuoteDataHelper $quoteDataHelper;

    private ProductRepositoryInterface $productRepository;

    private StoreManagerInterface $storeManager;

    private CookieManagerInterface $cookieManager;

    private CookieMetadataFactory $cookieMetadataFactory;

    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteDataHelper $quoteDataHelper,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        UserDataHelper $userDataHelper,
        CustomerSession $customerSession,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->checkoutSession       = $checkoutSession;
        $this->quoteDataHelper       = $quoteDataHelper;
        $this->productRepository     = $productRepository;
        $this->storeManager          = $storeManager;
        $this->customerSession       = $customerSession;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;

        parent::__construct($userDataHelper);
    }

    public function execute(Observer $observer): void
    {
        $this->customerSession->setDataLayer($this->getDataLayer());

        $meta = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath('/')
            ->setDuration(300)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie('taggrs_dl_pending', '1', $meta);
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
            return [];
        }

        $max      = 0;
        $lastItem = null;

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getId() > $max) {
                $max      = $quoteItem->getId();
                $lastItem = $quoteItem;
            }
        }

        if ($lastItem === null) {
            return [];
        }

        if ($lastItem->getProduct()->getTypeId() === 'configurable') {
            $configProduct        = $this->productRepository->getById($lastItem->getProduct()->getId());
            $item['item_id']      = $configProduct->getSku();
            $item['item_variant'] = $lastItem->getSku();
        } else {
            $item['item_id'] = $lastItem->getSku();
        }

        $item['item_name'] = $lastItem->getName();
        $item['price']     = (float) $lastItem->getPriceInclTax();
        $item['quantity']  = $lastItem->getQty();

        if ($lastItem->getDiscountAmount() > 0) {
            $item['discount'] = $lastItem->getDiscountAmount();
        }

        if ($lastItem->getQuote()->getCouponCode()) {
            $item['coupon'] = $lastItem->getQuote()->getCouponCode();
        }

        $item = array_merge($item, $this->quoteDataHelper->getCategoryNamesByProduct($lastItem->getProduct()));

        return [
            'value'     => (float) $this->checkoutSession->getQuote()->getGrandTotal(),
            'currency'  => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
            'items'     => [$item],
            'user_data' => $this->getUserData(),
        ];
    }
}
