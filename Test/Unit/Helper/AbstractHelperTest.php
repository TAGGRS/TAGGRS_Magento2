<?php

namespace Taggrs\DataLayer\Test\Unit\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\AbstractHelper;

class AbstractHelperTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private AbstractHelper $helper;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->helper = new AbstractHelper($this->categoryRepository);
    }

    public function testReturnsEmptyArrayWhenProductHasNoCategories(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([]);

        $this->assertSame([], $this->helper->getCategoryNamesByProduct($product));
    }

    public function testSingleCategoryWithoutPreloadedMapCallsRepository(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([5]);

        $category = $this->createMock(CategoryInterface::class);
        $category->method('getLevel')->willReturn(3);
        $category->method('getName')->willReturn('Cameras');

        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->with(5)
            ->willReturn($category);

        $result = $this->helper->getCategoryNamesByProduct($product);

        $this->assertSame(['item_category' => 'Cameras'], $result);
    }

    public function testMultipleCategoriesSortedDeepestFirst(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([10, 20, 30]);

        $cat10 = $this->createCategoryMock(2, 'Electronics');
        $cat20 = $this->createCategoryMock(4, 'Mirrorless');
        $cat30 = $this->createCategoryMock(3, 'Digital');

        $this->categoryRepository->method('get')->willReturnCallback(
            function ($id) use ($cat10, $cat20, $cat30) {
                return match((int) $id) {
                    10 => $cat10,
                    20 => $cat20,
                    30 => $cat30,
                    default => null,
                };
            }
        );

        $result = $this->helper->getCategoryNamesByProduct($product);

        $this->assertSame([
            'item_category'  => 'Mirrorless',
            'item_category2' => 'Digital',
            'item_category3' => 'Electronics',
        ], $result);
    }

    public function testMaxLimitIsRespected(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([1, 2, 3, 4, 5, 6]);

        $this->categoryRepository->method('get')->willReturnCallback(
            fn(int $id) => $this->createCategoryMock($id, "Cat$id")
        );

        $result = $this->helper->getCategoryNamesByProduct($product, 2);

        $this->assertCount(2, $result);
    }

    public function testPreloadedMapIsUsedWithoutRepositoryCall(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([5]);

        $this->categoryRepository->expects($this->never())->method('get');

        $result = $this->helper->getCategoryNamesByProduct($product, 5, [
            5 => ['name' => 'Cameras', 'level' => 3],
        ]);

        $this->assertSame(['item_category' => 'Cameras'], $result);
    }

    public function testIdsAbsentFromPreloadedMapAreSkipped(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([5, 99]);

        $this->categoryRepository->expects($this->never())->method('get');

        $result = $this->helper->getCategoryNamesByProduct($product, 5, [
            5 => ['name' => 'Cameras', 'level' => 3],
        ]);

        $this->assertSame(['item_category' => 'Cameras'], $result);
    }

    public function testRepositoryIsCachedOnSecondInvocation(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([5]);

        $category = $this->createCategoryMock(3, 'Cameras');

        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->with(5)
            ->willReturn($category);

        $this->helper->getCategoryNamesByProduct($product);
        $this->helper->getCategoryNamesByProduct($product);
    }

    public function testNoSuchEntityExceptionReturnsEmptyArray(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([5]);

        $this->categoryRepository->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame([], $this->helper->getCategoryNamesByProduct($product));
    }

    public function testZeroOrNegativeIdsAreIgnored(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([0, -1]);

        $this->categoryRepository->expects($this->never())->method('get');

        $this->assertSame([], $this->helper->getCategoryNamesByProduct($product));
    }

    public function testKeyNamesFollowItemCategoryConvention(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getCategoryIds')->willReturn([1, 2, 3]);

        $this->categoryRepository->method('get')->willReturnCallback(
            fn(int $id) => $this->createCategoryMock($id, "Cat$id")
        );

        $result = $this->helper->getCategoryNamesByProduct($product);

        $this->assertArrayHasKey('item_category', $result);
        $this->assertArrayHasKey('item_category2', $result);
        $this->assertArrayHasKey('item_category3', $result);
        $this->assertArrayNotHasKey('item_category1', $result);
    }

    private function createCategoryMock(int $level, string $name): CategoryInterface
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getLevel')->willReturn($level);
        $category->method('getName')->willReturn($name);
        return $category;
    }
}
