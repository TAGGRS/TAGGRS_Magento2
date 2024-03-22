<?php

namespace Taggrs\DataLayer\Block\Event;


use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class SearchResultsViewItemList extends DataLayer
{

    private RequestInterface $request;

    private ProductViewDataHelper $productHelper;

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
        $query = $this->request->getParam('q');
        $collection = $this->productHelper->getCurrentProductCollection();
        $items = $this->productHelper->getItemsByCollection($collection);

        return [
            'item_list_id' => 'search_results',
            'item_list_name' => 'Search Results for "'. $query .'"',
            'items' => $items,
        ];
    }
}
