<?php

namespace Taggrs\DataLayer\Test\Unit\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;
use Taggrs\DataLayer\Observer\AddToCart;

class AddToCartTest extends TestCase
{
    private CheckoutSession $checkoutSession;
    private CustomerSession $customerSession;
    private QuoteDataHelper $quoteDataHelper;
    private ProductRepositoryInterface $productRepository;
    private StoreManagerInterface $storeManager;
    private UserDataHelper $userDataHelper;
    private CookieManagerInterface $cookieManager;
    private CookieMetadataFactory $cookieMetadataFactory;
    private AddToCart $observer;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['setDataLayer'])
            ->getMock();

        $this->quoteDataHelper   = $this->createMock(QuoteDataHelper::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->userDataHelper    = $this->createMock(UserDataHelper::class);
        $this->cookieManager     = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $this->createMock(CookieMetadataFactory::class);

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency = $this->createMock(\Magento\Directory\Model\Currency::class);
        $currency->method('getCode')->willReturn('EUR');
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->quoteDataHelper->method('getCategoryNamesByProduct')->willReturn([]);

        $this->observer = new AddToCart(
            $this->checkoutSession,
            $this->quoteDataHelper,
            $this->productRepository,
            $this->storeManager,
            $this->userDataHelper,
            $this->customerSession,
            $this->cookieManager,
            $this->cookieMetadataFactory
        );
    }

    public function testGetEventReturnsAddToCart(): void
    {
        $this->assertSame('add_to_cart', $this->observer->getEvent());
    }

    public function testExecuteSetsDataLayerOnCustomerSession(): void
    {
        $this->checkoutSession->method('getQuote')->willReturn($this->buildEmptyQuote());
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')
            ->willReturn($this->buildFluentMeta());

        $this->customerSession->expects($this->once())->method('setDataLayer');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testExecuteSetsCookieTaggrsDlPending(): void
    {
        $this->checkoutSession->method('getQuote')->willReturn($this->buildEmptyQuote());
        $meta = $this->buildFluentMeta();
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $this->cookieManager->expects($this->once())
            ->method('setPublicCookie')
            ->with('taggrs_dl_pending', '1', $meta);

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testGetEcommerceReturnsEmptyOnNoSuchEntityException(): void
    {
        $this->checkoutSession->method('getQuote')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame([], $this->observer->getEcommerce());
    }

    public function testGetEcommerceReturnsEmptyWhenCartIsEmpty(): void
    {
        $this->checkoutSession->method('getQuote')->willReturn($this->buildEmptyQuote());

        $this->assertSame([], $this->observer->getEcommerce());
    }

    public function testGetEcommercePicksItemWithHighestId(): void
    {
        $item1 = $this->buildSimpleItem(1, 'SKU-1', 'Product 1', 10.0, 1.0);
        $item5 = $this->buildSimpleItem(5, 'SKU-5', 'Product 5', 20.0, 1.0);

        $quote = $this->buildQuoteWithItems([$item1, $item5], 20.0);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertSame('SKU-5', $result['items'][0]['item_id']);
    }

    public function testGetEcommerceIncludesGrandTotal(): void
    {
        $item = $this->buildSimpleItem(1, 'SKU-1', 'Product', 199.99, 1.0);
        $quote = $this->buildQuoteWithItems([$item], 199.99);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertSame(199.99, $result['value']);
    }

    public function testGetEcommerceIncludesCurrencyCode(): void
    {
        $item = $this->buildSimpleItem(1, 'SKU-1', 'Product', 10.0, 1.0);
        $quote = $this->buildQuoteWithItems([$item], 10.0);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertSame('EUR', $result['currency']);
    }

    public function testGetEcommerceIncludesDiscountWhenPositive(): void
    {
        $item = $this->buildSimpleItem(1, 'SKU-1', 'Product', 10.0, 1.0);
        $item->method('getDiscountAmount')->willReturn(2.0);

        $quote = $this->buildQuoteWithItems([$item], 8.0);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertArrayHasKey('discount', $result['items'][0]);
        $this->assertSame(2.0, $result['items'][0]['discount']);
    }

    public function testGetEcommerceOmitsDiscountWhenZero(): void
    {
        $item = $this->buildSimpleItem(1, 'SKU-1', 'Product', 10.0, 1.0);
        $item->method('getDiscountAmount')->willReturn(0.0);

        $quote = $this->buildQuoteWithItems([$item], 10.0);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertArrayNotHasKey('discount', $result['items'][0]);
    }

    public function testGetEcommerceIncludesItemVariantForConfigurableProduct(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getTypeId')->willReturn('configurable');
        $product->method('getId')->willReturn('100');

        $parentProduct = $this->createMock(ProductInterface::class);
        $parentProduct->method('getSku')->willReturn('CONFIG-PARENT');
        $this->productRepository->method('getById')->with('100')->willReturn($parentProduct);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku', 'getName', 'getQty', 'getProduct', 'getQuote'])
            ->addMethods(['getPriceInclTax', 'getDiscountAmount'])
            ->getMock();
        $item->method('getId')->willReturn(1);
        $item->method('getSku')->willReturn('VAR-SKU');
        $item->method('getName')->willReturn('Config Product');
        $item->method('getPriceInclTax')->willReturn(99.99);
        $item->method('getQty')->willReturn(1.0);
        $item->method('getDiscountAmount')->willReturn(0.0);
        $item->method('getProduct')->willReturn($product);
        $item->method('getQuote')->willReturn($this->buildEmptyQuote());

        $quote = $this->buildQuoteWithItems([$item], 99.99);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertSame('CONFIG-PARENT', $result['items'][0]['item_id']);
        $this->assertSame('VAR-SKU', $result['items'][0]['item_variant']);
    }

    public function testGetEcommerceIncludesUserData(): void
    {
        $this->userDataHelper->method('getUserData')->willReturn(['email' => 'x@x.com']);

        $item = $this->buildSimpleItem(1, 'SKU-1', 'Product', 10.0, 1.0);
        $quote = $this->buildQuoteWithItems([$item], 10.0);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->observer->getEcommerce();

        $this->assertSame(['email' => 'x@x.com'], $result['user_data']);
    }

    private function buildEmptyQuote(): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllVisibleItems'])
            ->addMethods(['getCouponCode', 'getGrandTotal'])
            ->getMock();
        $quote->method('getAllVisibleItems')->willReturn([]);
        $quote->method('getCouponCode')->willReturn(null);
        $quote->method('getGrandTotal')->willReturn(0.0);
        return $quote;
    }

    private function buildQuoteWithItems(array $items, float $grandTotal): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllVisibleItems'])
            ->addMethods(['getCouponCode', 'getGrandTotal'])
            ->getMock();
        $quote->method('getAllVisibleItems')->willReturn($items);
        $quote->method('getCouponCode')->willReturn(null);
        $quote->method('getGrandTotal')->willReturn($grandTotal);
        return $quote;
    }

    private function buildSimpleItem(int $id, string $sku, string $name, float $price, float $qty): QuoteItem
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getTypeId')->willReturn('simple');

        $itemQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCouponCode'])
            ->getMock();
        $itemQuote->method('getCouponCode')->willReturn(null);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku', 'getName', 'getQty', 'getProduct', 'getQuote'])
            ->addMethods(['getPriceInclTax', 'getDiscountAmount'])
            ->getMock();
        $item->method('getId')->willReturn($id);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getQty')->willReturn($qty);
        $item->method('getProduct')->willReturn($product);
        $item->method('getQuote')->willReturn($itemQuote);
        return $item;
    }

    private function buildFluentMeta(): PublicCookieMetadata
    {
        $meta = $this->createMock(PublicCookieMetadata::class);
        $meta->method('setPath')->willReturnSelf();
        $meta->method('setDuration')->willReturnSelf();
        $meta->method('setSameSite')->willReturnSelf();
        return $meta;
    }
}
