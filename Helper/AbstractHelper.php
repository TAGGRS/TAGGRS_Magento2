<?php

namespace Taggrs\DataLayer\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AbstractHelper
{
    protected CategoryRepositoryInterface $categoryRepository;

    /** @var array<int, string|null> */
    private array $categoryNameCache = [];

    /** @var array<int, int|null> */
    private array $categoryLevelCache = [];

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Return item_category..item_category5 (deepest first).
     *
     * @param ProductInterface $product
     * @param int $max
     * @param array<int, array{name:string, level:int}>|null $categoryMap
     */
    public function getCategoryNamesByProduct(
        ProductInterface $product,
        int $max = 5,
        ?array $categoryMap = null
    ): array {
        $ids = $product->getCategoryIds() ?: [];
        if (!$ids) {
            return [];
        }

        $levels = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            // Fast path: preloaded bulk map
            if ($categoryMap !== null) {
                if (isset($categoryMap[$id]['level'], $categoryMap[$id]['name'])) {
                    $levels[$id] = (int) $categoryMap[$id]['level'];
                    $this->categoryNameCache[$id] = (string) $categoryMap[$id]['name'];
                }
                continue;
            }

            // Fallback: repository with in-request cache
            if (!array_key_exists($id, $this->categoryLevelCache)) {
                try {
                    $cat = $this->categoryRepository->get($id);
                    $this->categoryLevelCache[$id] = (int) $cat->getLevel();
                    $this->categoryNameCache[$id]  = (string) $cat->getName();
                } catch (NoSuchEntityException $e) {
                    $this->categoryLevelCache[$id] = null;
                    $this->categoryNameCache[$id]  = null;
                }
            }

            if ($this->categoryLevelCache[$id] !== null) {
                $levels[$id] = $this->categoryLevelCache[$id];
            }
        }

        if (!$levels) {
            return [];
        }

        // Deepest first (highest level)
        arsort($levels);

        $topIds = array_slice(array_keys($levels), 0, $max);

        $out = [];
        $i = 1;
        foreach ($topIds as $id) {
            $name = $this->categoryNameCache[$id] ?? null;
            if (!$name) {
                continue;
            }

            $key = ($i === 1) ? 'item_category' : 'item_category' . $i;
            $out[$key] = $name;

            $i++;
            if ($i > $max) {
                break;
            }
        }

        return $out;
    }
}
