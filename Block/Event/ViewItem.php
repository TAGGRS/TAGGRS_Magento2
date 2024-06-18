<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class ViewItem extends DataLayer
{

    private RequestInterface $request;

    private ProductRepositoryInterface $productRepository;

    private ProductHelper $productHelper;

    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        UserDataHelper $userDataHelper,
        Template\Context $context,
        ProductHelper $productHelper,
        array $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->request           = $request;
        $this->productRepository = $productRepository;
        $this->productHelper     = $productHelper;
    }


    public function getEvent(): string
    {
        return 'view_item';
    }

    public function getEcommerce(): array
    {
        $product = $this->getCurrentProduct();

        $currency = $this->_storeManager
            ->getStore()
            ->getCurrentCurrency()
            ->getCode()
        ;

        $price = (float)$product->getFinalPrice();

        $item = [
            'item_id' => $product->getSku(),
            'item_name' => $product->getName(),
            'price' => $price,
        ];

        $item = array_merge($item, $this->productHelper->getCategoryNamesByProduct($product));

        return [
            'currency' => $currency,
            'value' => $price,
            'items' => [$item],
            'user_data' => $this->getUserData(),
        ];
    }

    private function getCurrentProduct(): ProductInterface
    {
        $id = $this->request->getParam('id');

        return $this->productRepository->getById($id);
    }
}
