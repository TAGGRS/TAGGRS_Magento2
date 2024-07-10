<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Renders a view_item event on the Product Detail Page
 */
class ViewItem extends DataLayer
{

    /**
     * @var RequestInterface request object
     */
    private RequestInterface $request;

    /**
     * @var ProductRepositoryInterface to retrieve products from database
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductHelper to retrieve specific product information
     */
    private ProductHelper $productHelper;

    /**
     * Class constructor
     *
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param UserDataHelper $userDataHelper
     * @param Context $context
     * @param ProductHelper $productHelper
     * @param array $data
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

    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string
    {
        return 'view_item';
    }

    /**
     * Get the e-commerce Data Layer
     *
     * @return array containing the e-commerce data
     */
    public function getEcommerce(): array
    {
        try {
            $product = $this->getCurrentProduct();
        } catch ( NoSuchEntityException $e ) {
            $product = null;
        }

        try {
            $currency = $this->_storeManager
                ->getStore()
                ->getCurrentCurrency()
                ->getCode()
            ;
        } catch ( NoSuchEntityException|LocalizedException $e ) {
            $currency = null;
        }

        if ($product !== null) {
            $price = (float)$product->getFinalPrice();

            $item = [
                'item_id' => $product->getSku(),
                'item_name' => $product->getName(),
                'price' => $price,
            ];

            $item = array_merge($item, $this->productHelper->getCategoryNamesByProduct($product));
        } else {
            $item = [];
            $price = 0;
        }

        return [
            'currency' => $currency,
            'value' => $price,
            'items' => [$item],
            'user_data' => $this->getUserData(),
        ];
    }

    /**
     * Get the current product using the ID from the URL.
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentProduct(): ProductInterface
    {
        $id = $this->request->getParam('id');

        return $this->productRepository->getById($id);
    }
}
