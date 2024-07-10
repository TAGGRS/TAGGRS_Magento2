<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Generates a Data Layer for the view_item_list event on the Catalog Search Results page
 */
class SearchResultsViewItemList extends DataLayer
{

    /**
     * @var RequestInterface the HTTP-request object
     */
    private RequestInterface $request;

    /**
     * @var ProductViewDataHelper to help retrieve product information
     */
    private ProductViewDataHelper $productHelper;

    /**
     * Class constructor
     *
     * @param RequestInterface $request
     * @param ProductViewDataHelper $productHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param UserDataHelper $userDataHelper
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        RequestInterface            $request,
        ProductViewDataHelper       $productHelper,
        CategoryRepositoryInterface $categoryRepository,
        UserDataHelper              $userDataHelper,
        Template\Context            $context,
        array                       $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->request            = $request;
        $this->productHelper = $productHelper;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string
    {
        return 'view_item_list';
    }

    /**
     * Get the e-commerce Data Layer
     *
     * @return array containing the e-commerce data
     */
    public function getEcommerce(): array
    {
        $query = $this->request->getParam('q');
        $collection = $this->productHelper->getCurrentProductCollection();
        $items = $this->productHelper->getItemsByCollection($collection);

        return [
            'item_list_id' => 'search_results',
            'item_list_name' => 'Search Results for "'. $query .'"',
            'items' => $items,
            'user_data' => $this->getUserData(),
        ];
    }
}
