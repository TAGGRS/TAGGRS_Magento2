<?php

namespace Taggrs\DataLayer\Block\Event;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\UserDataHelper;

class ViewItem extends DataLayer
{

    private RequestInterface $request;

    private ProductRepositoryInterface $productRepository;


    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        UserDataHelper $userDataHelper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct( $userDataHelper, $context, $data );

        $this->request           = $request;
        $this->productRepository = $productRepository;
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

        $price = $product->getFinalPrice();

        return [
            'currency' => $currency,
            'value' => $price,
            'items' => [[
                'item_id' => $product->getId(),
                'item_name' => $this->_escaper->escapeJs($product->getName()),
                'price' => $price,
                'item_category' => implode(',', $product->getCategoryIds()),
            ]]
        ];
    }

    private function getCurrentProduct(): ProductInterface
    {
        $id = $this->request->getParam( 'id' );

        return $this->productRepository->getById( $id );
    }
}
