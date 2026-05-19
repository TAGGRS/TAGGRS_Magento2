<?php

namespace Taggrs\DataLayer\Test\Unit\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

class QuoteDataHelperTest extends TestCase
{
    private CheckoutSession $checkoutSession;
    private CouponFactory $couponFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private ProductRepositoryInterface $productRepository;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private StoreManagerInterface $storeManager;
    private QuoteDataHelper $helper;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();

        $this->couponFactory             = $this->createMock(CouponFactory::class);
        $this->categoryRepository        = $this->createMock(CategoryRepositoryInterface::class);
        $this->productRepository         = $this->createMock(ProductRepositoryInterface::class);
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $this->storeManager              = $this->createMock(StoreManagerInterface::class);

        $this->helper = new QuoteDataHelper(
            $this->checkoutSession,
            $this->couponFactory,
            $this->categoryRepository,
            $this->productRepository,
            $this->categoryCollectionFactory,
            $this->storeManager
        );
    }

    public function testGetItemsFromQuoteReturnsEmptyArrayOnEmptyCart(): void
    {
        $quote = $this->buildQuoteMock([]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->assertSame([], $this->helper->getItemsFromQuote());
    }

    public function testGetItemsFromQuoteReturnsSimpleProductFields(): void
    {
        $item = $this->buildSimpleItem('SKU-001', 'Widget', 29.99, 2.0);
        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote();

        $this->assertCount(1, $result);
        $this->assertSame('SKU-001', $result[0]['item_id']);
        $this->assertSame('Widget', $result[0]['item_name']);
        $this->assertSame(29.99, $result[0]['price']);
        $this->assertSame(2.0, $result[0]['quantity']);
    }

    public function testGetItemsFromQuoteIncludesDiscountWhenFlagTrue(): void
    {
        $item = $this->buildSimpleItem('SKU-001', 'Widget', 29.99, 1.0);
        $item->method('getDiscountAmount')->willReturn(5.0);

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote(true);

        $this->assertArrayHasKey('discount', $result[0]);
        $this->assertSame(5.0, $result[0]['discount']);
    }

    public function testGetItemsFromQuoteOmitsDiscountWhenFlagFalse(): void
    {
        $item = $this->buildSimpleItem('SKU-001', 'Widget', 29.99, 1.0);
        $item->method('getDiscountAmount')->willReturn(5.0);

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote(false);

        $this->assertArrayNotHasKey('discount', $result[0]);
    }

    public function testGetItemsFromQuoteIncludesCouponCodeWhenFlagTrue(): void
    {
        $item = $this->buildSimpleItem('SKU-001', 'Widget', 29.99, 1.0);

        $itemQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCouponCode'])
            ->getMock();
        $itemQuote->method('getCouponCode')->willReturn('SUMMER10');
        $item->method('getQuote')->willReturn($itemQuote);

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote(false, true);

        $this->assertArrayHasKey('coupon', $result[0]);
        $this->assertSame('SUMMER10', $result[0]['coupon']);
    }

    public function testGetItemsFromQuoteHandlesConfigurableProductWithSimpleSku(): void
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getCategoryIds')->willReturn([]);
        $product->method('getTypeId')->willReturn('configurable');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getName', 'getQty', 'getProduct', 'getQuote'])
            ->addMethods(['getPriceInclTax', 'getDiscountAmount', 'getProductOptionByCode'])
            ->getMock();
        $item->method('getSku')->willReturn('PARENT-SKU');
        $item->method('getName')->willReturn('Config Product');
        $item->method('getPriceInclTax')->willReturn(99.99);
        $item->method('getQty')->willReturn(1.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getProduct')->willReturn($product);
        $item->method('getProductOptionByCode')->with('simple_sku')->willReturn('VAR-001');
        $item->method('getQuote')->willReturn($this->buildQuoteMock([]));

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote();

        $this->assertSame('PARENT-SKU', $result[0]['item_id']);
        $this->assertSame('VAR-001', $result[0]['item_variant']);
    }

    public function testGetItemsFromQuoteHandlesConfigurableWithSuperProductConfig(): void
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getCategoryIds')->willReturn([]);
        $product->method('getTypeId')->willReturn('configurable');

        $parentProduct = $this->createMock(ProductInterface::class);
        $parentProduct->method('getSku')->willReturn('CONFIG-PARENT-SKU');
        $this->productRepository->method('getById')->with(42)->willReturn($parentProduct);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getName', 'getQty', 'getProduct', 'getQuote'])
            ->addMethods(['getPriceInclTax', 'getDiscountAmount', 'getProductOptionByCode'])
            ->getMock();
        $item->method('getSku')->willReturn('VARIANT-SKU');
        $item->method('getName')->willReturn('Config Product');
        $item->method('getPriceInclTax')->willReturn(99.99);
        $item->method('getQty')->willReturn(1.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getProduct')->willReturn($product);
        $item->method('getProductOptionByCode')->willReturnMap([
            ['simple_sku', ''],
            ['super_product_config', ['product_id' => 42]],
        ]);
        $item->method('getQuote')->willReturn($this->buildQuoteMock([]));

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getItemsFromQuote();

        $this->assertSame('CONFIG-PARENT-SKU', $result[0]['item_id']);
        $this->assertSame('VARIANT-SKU', $result[0]['item_variant']);
    }

    public function testGetItemByProductReturnsFinalPriceForConfigurableProduct(): void
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getTypeId')->willReturn('configurable');
        $product->method('getId')->willReturn('P1');
        $product->method('getName')->willReturn('Config');
        $product->method('getPrice')->willReturn(100.0);
        $product->method('getFinalPrice')->willReturn(90.0);
        $product->method('getCategoryIds')->willReturn([]);

        $result = $this->helper->getItemByProduct($product);

        $this->assertSame(90.0, $result['price']);
    }

    public function testGetItemByProductReturnsPriceForSimpleProduct(): void
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getId')->willReturn('P2');
        $product->method('getName')->willReturn('Simple');
        $product->method('getPrice')->willReturn(49.99);
        $product->method('getCategoryIds')->willReturn([]);

        $result = $this->helper->getItemByProduct($product);

        $this->assertSame(49.99, $result['price']);
    }

    public function testGetCouponFromQuoteReturnsNullWhenNoCouponCode(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCouponCode'])
            ->getMock();
        $quote->method('getCouponCode')->willReturn(null);

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->assertNull($this->helper->getCouponFromQuote());
    }

    public function testGetCouponFromQuoteReturnsNullOnSessionException(): void
    {
        $this->checkoutSession->method('getQuote')
            ->willThrowException(new NoSuchEntityException());

        $this->assertNull($this->helper->getCouponFromQuote());
    }

    public function testGetCouponFromQuoteReturnsCouponWhenCodeIsValid(): void
    {
        $coupon = $this->createMock(Coupon::class);
        $coupon->method('loadByCode')->willReturn($coupon);

        $this->couponFactory->method('create')->willReturn($coupon);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCouponCode'])
            ->getMock();
        $quote->method('getCouponCode')->willReturn('PROMO20');

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getCouponFromQuote();

        $this->assertSame($coupon, $result);
    }

    public function testGetQuoteDataReturnsEmptyOnLocalizedException(): void
    {
        $this->checkoutSession->method('getQuote')
            ->willThrowException(new LocalizedException(__('Error')));

        $this->assertSame([], $this->helper->getQuoteData());
    }

    public function testGetQuoteDataIsKeyedByItemId(): void
    {
        $item = $this->buildSimpleItem('SKU-001', 'Widget', 29.99, 1.0);
        $item->method('getItemId')->willReturn(7);

        $quote = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getQuoteData();

        $this->assertArrayHasKey(7, $result);
        $this->assertSame('SKU-001', $result[7]['item_id']);
    }

    private function buildQuoteMock(array $items): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllVisibleItems', 'getItems'])
            ->addMethods(['getCouponCode'])
            ->getMock();
        $quote->method('getAllVisibleItems')->willReturn($items);
        $quote->method('getItems')->willReturn($items);
        $quote->method('getCouponCode')->willReturn(null);
        return $quote;
    }

    private function buildSimpleItem(string $sku, string $name, float $price, float $qty): QuoteItem
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getCategoryIds')->willReturn([]);
        $product->method('getTypeId')->willReturn('simple');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getName', 'getItemId', 'getQty', 'getProduct', 'getQuote'])
            ->addMethods(['getPriceInclTax', 'getDiscountAmount', 'getProductOptionByCode'])
            ->getMock();
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getQty')->willReturn($qty);
        $item->method('getProduct')->willReturn($product);
        $item->method('getProductOptionByCode')->willReturn(null);
        return $item;
    }
}
