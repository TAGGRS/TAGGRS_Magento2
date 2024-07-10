<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\CouponFactory;

/**
 * To retrieve data from current customer quote
 */
class QuoteDataHelper extends AbstractHelper
{
    /**
     * @var Session to retrieve data from current customer quote
     */
    private Session $checkoutSession;

    /**
     * @var CouponFactory to retrieve information about applied coupons
     */
    private CouponFactory $couponFactory;

    /**
     * @var ProductRepositoryInterface to retrieve product objects from the database
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * Class constructor
     *
     * @param Session $checkoutSession
     * @param CouponFactory $couponFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Session $checkoutSession,
        CouponFactory $couponFactory,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->couponFactory = $couponFactory;
        $this->productRepository = $productRepository;

        parent::__construct($categoryRepository);
    }

    /**
     * Get item array from product object
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function getItemByProduct(ProductInterface $product): array
    {
        $item = [
            'item_id' => $product->getId(),
            'item_name' => $product->getName(),
            'price' => $product->getTypeId() !== 'configurable' ? $product->getPrice() : $product->getFinalPrice(),
        ];

        return array_merge($item, $this->getCategoryNamesByProduct($product));
    }


    public function getItemsFromQuote(bool $includeDiscount = false, bool $includeCouponCode = false): array
    {
        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();

        $items = [];
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getProduct()->getTypeId() === 'configurable') {
                $configProduct = $this->productRepository->getById($quoteItem->getProduct()->getId());
                $item['item_id'] = $configProduct->getSku();
                $item['item_variant'] = $quoteItem->getSku();
            } else {
                $item['item_id'] = $quoteItem->getSku();
            }

            $item['item_name'] = $quoteItem->getName();
            $item['price'] = (float)$quoteItem->getPriceInclTax();
//            $item['item_category'] = implode(',', $quoteItem->getProduct()->getCategoryIds() );
            $item['quantity'] = $quoteItem->getQty();

            if ($includeDiscount) {
                $item['discount'] = $quoteItem->getDiscountAmount();
            }

            if ($quoteItem->getQuote()->getCouponCode()) {
                $item['coupon'] = $quoteItem->getQuote()->getCouponCode();
            }

            $item = $item + $this->getCategoryNamesByProduct($quoteItem->getProduct());

            $items[] = $item;

        }

        return $items;
    }

    public function getQuoteData(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $data = [];
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $item = [
                    'item_id' => $quoteItem->getSku(),
                    'item_name' => $quoteItem->getName(),
                    'price' => floatval($quoteItem->getPriceInclTax()),
                    'quantity' => $quoteItem->getQty(),
                ];

                if ($quoteItem->getProduct()->getTypeId() === 'configurable') {
                    $configProduct = $this->productRepository->getById($quoteItem->getProduct()->getId());
                    $item['item_id'] = $configProduct->getSku();
                    $item['item_variant'] = $quoteItem->getSku();
                } else {
                    $item['item_id'] = $quoteItem->getSku();
                }

                $item = $item + $this->getCategoryNamesByProduct($quoteItem->getProduct());
                $data[$quoteItem->getItemId()] = $item;
            }
            return $data;
        } catch (NoSuchEntityException|LocalizedException $e) {
            return [];
        }
    }

    public function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

    public function getCouponFromQuote(): ?CouponInterface
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $couponCode = $quote->getCouponCode();

            if ($couponCode === null) {
                return null;
            }

            $coupon = $this->getCouponByCode($couponCode);

            if ($coupon instanceof CouponInterface) {
                return $coupon;
            }

        } catch (NoSuchEntityException|LocalizedException $e) {
        }

        return null;
    }

    public function getCouponByCode(string $couponCode)
    {
        return $this->couponFactory->create()->loadByCode($couponCode);
    }
}
