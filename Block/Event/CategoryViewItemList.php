<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Block\DataLayer;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

/**
 * Generate a Data Layer for the view_item_list event on the Catalog Category Page
 */
class CategoryViewItemList extends DataLayer
{

    /**
     * @var RequestInterface the request object
     */
    private RequestInterface $request;

    /**
     * @var ProductViewDataHelper to retrieve product details
     */
    private ProductViewDataHelper $productHelper;

    /**
     * @var CategoryRepositoryInterface to retrieve category objects from the database
     */
    private CategoryRepositoryInterface $categoryRepository;

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
        try {
            $category = $this->categoryRepository->get( $this->request->getParam( 'id' ) );
            $collection = $this->productHelper->getCurrentProductCollection();
            $items = $this->productHelper->getItemsByCollection($collection);
        } catch ( NoSuchEntityException $e ) {
            $items = [];
            $category = null;
        }

        return [
            'item_list_id' => $category !== null ? $category->getId() : null,
            'item_list_name' => $category !== null ? $category->getName() : null,
            'items' => $items,
            'user_data' => $this->getUserData(),
        ];
    }
}
