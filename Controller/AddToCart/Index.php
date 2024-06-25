<?php

namespace Taggrs\DataLayer\Controller\AddToCart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{

    private Session $checkoutSession;

    private QuoteDataHelper $quoteDataHelper;

    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        UserDataHelper $userDataHelper,
        StoreManagerInterface $storeManager,
        QuoteDataHelper $quoteDataHelper,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $resultJsonFactory, $userDataHelper, $storeManager);

        $this->checkoutSession = $checkoutSession;
        $this->quoteDataHelper = $quoteDataHelper;
        $this->productRepository = $productRepository;
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
        }

        if (!isset($quote)) {
            return [];
        }

        $max = 0;
        $lastItem = null;

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getId() > $max) {
                $max = $quoteItem->getId();
                $lastItem = $quoteItem;
            }
        }

        if ($lastItem === null) {
            return [];
        }

        if ($lastItem->getProduct()->getTypeId() === 'configurable') {
            $configProduct = $this->productRepository->getById($lastItem->getProduct()->getId());
            $item['item_id'] = $configProduct->getSku();
            $item['item_variant'] = $lastItem->getSku();
        } else {
            $item['item_id'] = $lastItem->getSku();
        }

        $item['item_name'] = $lastItem->getName();
        $item['price'] = (float)$lastItem->getPriceInclTax();
        $item['quantity'] = $lastItem->getQty();

        if ($lastItem->getDiscountAmount() > 0) {
            $item['discount'] = $lastItem->getDiscountAmount();
        }

        if ($lastItem->getQuote()->getCouponCode()) {
            $item['coupon'] = $lastItem->getQuote()->getCouponCode();
        }

        $item = array_merge($item, $this->quoteDataHelper->getCategoryNamesByProduct($lastItem->getProduct()));

        return [
            'items' => [$item]
        ];
    }
}
