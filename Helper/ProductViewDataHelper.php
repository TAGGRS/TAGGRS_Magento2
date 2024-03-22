<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class ProductViewDataHelper
{
    private Escaper $escaper;

    private ListProduct $listProduct;

    private CheckoutSession $checkoutSession;

    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Escaper $escaper,
        ListProduct $listProduct,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->escaper = $escaper;
        $this->listProduct = $listProduct;
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
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
        return [
            'item_id' => $product->getId(),
            'item_name' => $this->escaper->escapeJs($product->getName()),
            'price' => $product->getTypeId() !== 'configurable' ? $product->getPrice() : $product->getFinalPrice(),
            'item_category' => implode(',', $product->getCategoryIds() )
        ];
    }



    public function getItemsFromOrder(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $product = $this->productRepository->getById($orderItem->getProductId());
            $item = $this->getItemByProduct($product);
            $item['price'] = $orderItem->getPrice();
            $item['quantity'] = (int)$orderItem->getQtyOrdered();

            $items[] = $item;
        }

        return $items;

    }
}
