<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\CouponFactory;

class QuoteDataHelper
{
    private Session $checkoutSession;

    private Escaper $escaper;

    private CouponFactory $couponFactory;

    /**
     * @param Session $checkoutSession
     * @param Escaper $escaper
     * @param CouponFactory $couponFactory
     */
    public function __construct(Session $checkoutSession, Escaper $escaper, CouponFactory $couponFactory)
    {
        $this->checkoutSession = $checkoutSession;
        $this->escaper = $escaper;
        $this->couponFactory = $couponFactory;
    }

    public function getItemByProduct( ProductInterface $product ): array
    {
        return [
            'item_id' => $product->getId(),
            'item_name' => $this->escaper->escapeJs($product->getName()),
            'price' => $product->getTypeId() !== 'configurable' ? $product->getPrice() : $product->getFinalPrice(),
            'item_category' => implode(',', $product->getCategoryIds() )
        ];
    }

    public function getItemsFromQuote(bool $includeDiscount = false, bool $includeCouponCode = false): array
    {
        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();

        $items = [];
        foreach ($quoteItems as $quoteItem) {
            $item = $this->getItemByProduct($quoteItem->getProduct());
            $item['price'] = $quoteItem->getPriceInclTax();
            $item['quantity'] = $quoteItem->getQty();

            if ($includeDiscount) {
                $item['discount'] = $quoteItem->getDiscountAmount();
            }

            if ($includeCouponCode) {
                $item['coupon'] = $quoteItem->getQuote()->getCouponCode();
            }

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
                $data[$quoteItem->getId()] = [
                    'item_id' => $quoteItem->getProduct()->getId(),
                    'item_name' => $this->escaper->escapeJs($quoteItem->getProduct()->getName()),
                    'price' => floatval($quoteItem->getPriceInclTax()),
                    'quantity' => $quoteItem->getQty()
                ];
            }
            return $data;
        } catch (NoSuchEntityException $e) {
        } catch (LocalizedException $e) {
        }

        return [];
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
