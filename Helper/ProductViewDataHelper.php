<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Sales\Api\Data\OrderInterface;

class ProductViewDataHelper extends AbstractHelper
{

    private ListProduct $listProduct;

    private ProductRepositoryInterface $productRepository;

    public function __construct(
        ListProduct $listProduct,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository
    )
    {
        $this->listProduct = $listProduct;
        $this->productRepository = $productRepository;

        parent::__construct($categoryRepository);
    }

    public function getCurrentProductCollection(): AbstractCollection
    {
        return $this->listProduct->getLoadedProductCollection();
    }

    public function getItemsByCollection( AbstractCollection $collection): array
    {
        $items = [];

        /** @var ProductInterface $product */
        foreach ($collection->getItems() as $product) {
            $items[] = $this->getItemByProduct($product);
        }

        return $items;
    }

    public function getItemByProduct( ProductInterface $product ): array
    {
        $item = [
            'item_id' => $product->getSku(),
            'item_name' => $product->getName(),
            'price' => $product->getTypeId() !== 'configurable' ? (float)$product->getPrice() : (float)$product->getFinalPrice(),
        ];

        return array_merge($item, $this->getCategoryNamesByProduct($product));
    }



    public function getItemsFromOrder(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getParentItemId() !== null) {
                continue;
            }
            $product = $this->productRepository->getById($orderItem->getProductId());


            $item = [];
            $item['item_id'] = $product->getSku();
            if ($product->getSku() !== $orderItem->getSku()) {
                $item['item_variant'] = $orderItem->getSku();
            }

            $item['item_name'] = $orderItem->getName();
            $item['price'] = (float)$orderItem->getPrice();
            $item['quantity'] = (int)$orderItem->getQtyOrdered();
            if ($order->getCouponCode()) {
                $item['coupon'] = $order->getCouponCode();
            }

            $item = array_merge($item, $this->getCategoryNamesByProduct($product));

            $items[] = $item;
        }

        return $items;

    }
}
