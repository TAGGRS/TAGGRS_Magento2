<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * To retrieve data from current customer quote
 */
class QuoteDataHelper extends AbstractHelper
{
    private Session $checkoutSession;
    private CouponFactory $couponFactory;
    private ProductRepositoryInterface $productRepository;

    private CategoryCollectionFactory $categoryCollectionFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Session $checkoutSession,
        CouponFactory $couponFactory,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->couponFactory = $couponFactory;
        $this->productRepository = $productRepository;

        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;

        parent::__construct($categoryRepository);
    }

    /**
     * Bulk-load category name + level for all categories used in the quote.
     *
     * @return array<int, array{name:string, level:int}>
     */
    private function preloadCategoryMapFromQuote(Quote $quote): array
    {
        $ids = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $p = $item->getProduct();
            if (!$p) {
                continue;
            }

            foreach (($p->getCategoryIds() ?: []) as $cid) {
                $cid = (int) $cid;
                if ($cid > 0) {
                    $ids[$cid] = true;
                }
            }
        }

        $ids = array_keys($ids);
        if (!$ids) {
            return [];
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect(['name', 'level']);
        $collection->addAttributeToFilter('entity_id', ['in' => $ids]);

        $map = [];
        foreach ($collection as $cat) {
            $id = (int) $cat->getId();
            $name = (string) $cat->getName();
            if ($id && $name !== '') {
                $map[$id] = [
                    'name' => $name,
                    'level' => (int) $cat->getLevel(),
                ];
            }
        }

        return $map;
    }

    /**
     * Resolve parent/variant skus without productRepository calls.
     *
     * @return array{item_id:string, item_variant?:string}
     */
    private function resolveItemIdsFromQuoteItem($quoteItem): array
    {
        // Default: simple / non-configurable
        $itemId = (string) $quoteItem->getSku();
        $out = ['item_id' => $itemId];

        $product = $quoteItem->getProduct();
        if (!$product) {
            return $out;
        }

        if ($product->getTypeId() !== 'configurable') {
            return $out;
        }

        // Configurable: try to get the selected simple sku from options
        $simpleSku = $quoteItem->getProductOptionByCode('simple_sku');

        // Many setups: quoteItem sku = parent sku, simple_sku = variant
        if (is_string($simpleSku) && $simpleSku !== '') {
            $out['item_variant'] = $simpleSku;
            // keep item_id as parent sku (quoteItem sku)
            return $out;
        }

        // Fallback: sometimes quoteItem sku is simple sku; try to infer parent from "super_product_config"
        $cfg = $quoteItem->getProductOptionByCode('super_product_config');
        if (is_array($cfg) && !empty($cfg['product_id'])) {
            try {
                $parent = $this->productRepository->getById((int)$cfg['product_id']);
                $out['item_id'] = (string) $parent->getSku();
                $out['item_variant'] = (string) $quoteItem->getSku();
            } catch (\Throwable $e) {
                // keep defaults
            }
        }

        return $out;
    }

    public function getItemByProduct(ProductInterface $product): array
    {
        $item = [
            'item_id' => $product->getId(),
            'item_name' => $product->getName(),
            'price' => $product->getTypeId() !== 'configurable' ? $product->getPrice() : $product->getFinalPrice(),
        ];

        // No bulk map here (single product context), fallback path is cached anyway
        return array_merge($item, $this->getCategoryNamesByProduct($product, 5, null));
    }

    public function getItemsFromQuote(bool $includeDiscount = false, bool $includeCouponCode = false): array
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteItems = $quote->getAllVisibleItems();

        $categoryMap = $this->preloadCategoryMapFromQuote($quote);

        $items = [];
        foreach ($quoteItems as $quoteItem) {
            $item = []; // IMPORTANT: reset per item

            // id/variant without repo call
            $ids = $this->resolveItemIdsFromQuoteItem($quoteItem);
            $item['item_id'] = $ids['item_id'];
            if (!empty($ids['item_variant'])) {
                $item['item_variant'] = $ids['item_variant'];
            }

            $item['item_name'] = $quoteItem->getName();
            $item['price'] = (float) $quoteItem->getPriceInclTax();
            $item['quantity'] = $quoteItem->getQty();

            if ($includeDiscount) {
                $item['discount'] = $quoteItem->getDiscountAmount();
            }

            if ($includeCouponCode && $quoteItem->getQuote()->getCouponCode()) {
                $item['coupon'] = $quoteItem->getQuote()->getCouponCode();
            }

            // Bulk categories + max 5 + deepest first
            $item = array_merge($item, $this->getCategoryNamesByProduct($quoteItem->getProduct(), 5, $categoryMap));

            $items[] = $item;
        }

        return $items;
    }

    public function getQuoteData(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $categoryMap = $this->preloadCategoryMapFromQuote($quote);

            $data = [];
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $ids = $this->resolveItemIdsFromQuoteItem($quoteItem);

                $item = [
                    'item_id' => $ids['item_id'],
                    'item_name' => $quoteItem->getName(),
                    'price' => (float) $quoteItem->getPriceInclTax(),
                    'quantity' => $quoteItem->getQty(),
                ];

                if (!empty($ids['item_variant'])) {
                    $item['item_variant'] = $ids['item_variant'];
                }

                $item = $item + $this->getCategoryNamesByProduct($quoteItem->getProduct(), 5, $categoryMap);
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
