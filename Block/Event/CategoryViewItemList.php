<?php

namespace Taggrs\DataLayer\Block\Event;


use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class CategoryViewItemList extends DataLayer
{

    private RequestInterface $request;

    private ProductViewDataHelper $productHelper;

    private CategoryRepositoryInterface $categoryRepository;


    public function __construct(
        RequestInterface            $request,
        ProductViewDataHelper       $productHelper,
        CategoryRepositoryInterface $categoryRepository,
        UserDataHelper              $userDataHelper,
        Template\Context            $context,
        array                       $data = []
    ) {
        parent::__construct( $userDataHelper, $context, $data );

        $this->request            = $request;
        $this->productHelper = $productHelper;
        $this->categoryRepository = $categoryRepository;
    }


    public function getEvent(): string
    {
        return 'view_item_list';
    }

    public function getEcommerce(): array
    {
        $category = $this->categoryRepository->get( $this->request->getParam( 'id' ) );
        $collection = $this->productHelper->getCurrentProductCollection();
        $items = $this->productHelper->getItemsByCollection($collection);

        return [
            'item_list_id' => $category->getId(),
            'item_list_name' => $this->_escaper->escapeJs($category->getName()),
            'items' => $items,
        ];
    }
}
