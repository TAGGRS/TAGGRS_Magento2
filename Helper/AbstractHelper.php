<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AbstractHelper
{
    protected CategoryRepositoryInterface $categoryRepository;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }


    public function getCategoryNamesByProduct(ProductInterface $product): array
    {
        $categories = [];

        foreach ($product->getCategoryIds() as $categoryId) {
            try {
                $key = 'item_category';
                if (count($categories) > 0) {
                    $key .= count($categories) + 1;
                }
                $category     = $this->categoryRepository->get($categoryId);
                $categories[$key] = $category->getName();
            } catch (NoSuchEntityException $e) {
            }
        }

        return $categories;
    }
}
