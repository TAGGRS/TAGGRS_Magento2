<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

class ProductHelper extends AbstractHelper
{
    private Escaper $escaper;

    private ListProduct $listProduct;

    private CheckoutSession $checkoutSession;

    private ProductRepositoryInterface $productRepository;


    /**
     * @param Escaper $escaper
     */
    public function __construct(
        Escaper $escaper,
        ListProduct $listProduct,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
    ) {
        $this->escaper            = $escaper;
        $this->listProduct        = $listProduct;
        $this->checkoutSession    = $checkoutSession;
        $this->productRepository  = $productRepository;
        $this->categoryRepository = $categoryRepository;

        parent::__construct($categoryRepository);
    }

    public function getCurrentProductCollection(): AbstractCollection
    {
        return $this->listProduct->getLoadedProductCollection();
    }

    public function getItemsByCollection(AbstractCollection $collection)
    {
        $items = [];

        /** @var ProductInterface $item */
        foreach ($collection->getItems() as $item) {
            $items[] = $this->getItemByProduct($item);
        }

        return $items;
    }

    public function getItemByProduct(ProductInterface $product): array
    {
        $item = [
            'item_id'       => $product->getSku(),
            'item_name'     => $product->getName(),
            'price'         => $product->getTypeId() !== 'configurable' ? $product->getPrice() : $product->getFinalPrice(),
        ];

        $item = array_merge($item, $this->getCategoryNamesByProduct($product));

        return $item;
    }

    public function getItemsFromQuote(): array
    {
        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $items      = [];
        foreach ($quoteItems as $quoteItem) {
            $item             = $this->getItemByProduct($quoteItem->getProduct());
            $item['item_id']  = $quoteItem->getSku();
            $item['price']    = $quoteItem->getPriceInclTax();
            $item['quantity'] = $quoteItem->getQty();


            $items[]          = $item;

        }

        return $items;
    }

    public function getItemsFromOrder(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $product          = $this->productRepository->getById($orderItem->getProductId());
            $item             = $this->getItemByProduct($product);
            $item['price']    = $orderItem->getPrice();
            $item['quantity'] = (int) $orderItem->getQtyOrdered();

            $items[] = $item;
        }

        return $items;
    }
}
