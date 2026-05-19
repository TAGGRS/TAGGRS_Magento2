<?php

namespace Taggrs\DataLayer\Test\Unit\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Controller\Cart\Delete;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\ProductViewDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;
use Taggrs\DataLayer\Plugin\RemoveFromCart;

class RemoveFromCartTest extends TestCase
{
    private Session $customerSession;
    private UserDataHelper $userDataHelper;
    private ProductViewDataHelper $productDataHelper;
    private CartItemRepositoryInterface $cartItemRepository;
    private CheckoutSession $checkoutSession;
    private StoreManagerInterface $storeManager;
    private RemoveFromCart $plugin;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['setDataLayer'])
            ->getMock();

        $this->userDataHelper    = $this->createMock(UserDataHelper::class);
        $this->productDataHelper = $this->createMock(ProductViewDataHelper::class);
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency = $this->createMock(\Magento\Directory\Model\Currency::class);
        $currency->method('getCode')->willReturn('EUR');
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->productDataHelper->method('getCategoryNamesByProduct')->willReturn([]);

        $this->plugin = new RemoveFromCart(
            $this->customerSession,
            $this->userDataHelper,
            $this->productDataHelper,
            $this->cartItemRepository,
            $this->checkoutSession,
            $this->storeManager
        );
    }

    public function testGetEventReturnsRemoveFromCart(): void
    {
        $this->assertSame('remove_from_cart', $this->plugin->getEvent());
    }

    public function testGetDataLayerHasEventAndEcommerceKeys(): void
    {
        $quote = $this->buildQuoteMock([]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->plugin->getDataLayer();

        $this->assertArrayHasKey('event', $result);
        $this->assertArrayHasKey('ecommerce', $result);
        $this->assertSame('remove_from_cart', $result['event']);
    }

    public function testBeforeExecuteDoesNothingWhenNoItemIdParam(): void
    {
        $subject = $this->buildDeleteSubject(null);

        $this->customerSession->expects($this->never())->method('setDataLayer');

        $this->plugin->beforeExecute($subject);
    }

    public function testBeforeExecuteDoesNothingWhenItemNotFoundInQuote(): void
    {
        $subject = $this->buildDeleteSubject('999');
        $item    = $this->buildQuoteItemMock(1, 'SKU', 'Product', 10.0, 1.0);
        $quote   = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->customerSession->expects($this->never())->method('setDataLayer');

        $this->plugin->beforeExecute($subject);
    }

    public function testBeforeExecuteSetsDataLayerWhenItemFound(): void
    {
        $subject = $this->buildDeleteSubject('3');
        $item    = $this->buildQuoteItemMock(3, 'SKU-003', 'Widget', 15.0, 2.0);
        $quote   = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->customerSession->expects($this->once())->method('setDataLayer');

        $this->plugin->beforeExecute($subject);
    }

    public function testGetEcommerceAlwaysHasCurrencyKey(): void
    {
        $quote = $this->buildQuoteMock([]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertArrayHasKey('currency', $ecommerce);
        $this->assertSame('EUR', $ecommerce['currency']);
    }

    public function testGetEcommerceValueIsPriceTimesQtyAfterBeforeExecute(): void
    {
        $subject = $this->buildDeleteSubject('3');
        $item    = $this->buildQuoteItemMock(3, 'SKU-003', 'Widget', 15.0, 3.0);
        $quote   = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->plugin->beforeExecute($subject);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertSame(45.0, $ecommerce['value']);
    }

    public function testGetEcommerceItemArrayContainsCorrectFields(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn('P1');
        $product->method('getName')->willReturn('Widget');

        $subject = $this->buildDeleteSubject('3');
        $item    = $this->buildQuoteItemMockWithProduct(3, $product, 15.0, 2.0);
        $quote   = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->plugin->beforeExecute($subject);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertSame('P1', $ecommerce['items'][0]['item_id']);
        $this->assertSame('Widget', $ecommerce['items'][0]['item_name']);
        $this->assertSame(2.0, $ecommerce['items'][0]['quantity']);
        $this->assertSame(15.0, $ecommerce['items'][0]['price']);
    }

    public function testGetEcommerceContainsUserDataAfterBeforeExecute(): void
    {
        $this->userDataHelper->method('getUserData')->willReturn(['email' => 'x@y.com']);

        $subject = $this->buildDeleteSubject('3');
        $item    = $this->buildQuoteItemMock(3, 'SKU-003', 'Widget', 15.0, 1.0);
        $quote   = $this->buildQuoteMock([$item]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->plugin->beforeExecute($subject);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertArrayHasKey('user_data', $ecommerce);
        $this->assertSame(['email' => 'x@y.com'], $ecommerce['user_data']);
    }

    public function testGetEcommerceHasOnlyCurrencyWhenNoItemRemoved(): void
    {
        $quote = $this->buildQuoteMock([]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertArrayHasKey('currency', $ecommerce);
        $this->assertArrayNotHasKey('items', $ecommerce);
        $this->assertArrayNotHasKey('value', $ecommerce);
        $this->assertArrayNotHasKey('user_data', $ecommerce);
    }

    private function buildDeleteSubject(?string $itemId): Delete
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('id')->willReturn($itemId);

        $subject = $this->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();
        $subject->method('getRequest')->willReturn($request);

        return $subject;
    }

    private function buildQuoteMock(array $items): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->method('getItems')->willReturn($items);
        return $quote;
    }

    private function buildQuoteItemMock(int $id, string $sku, string $productName, float $price, float $qty): QuoteItem
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($sku);
        $product->method('getName')->willReturn($productName);

        return $this->buildQuoteItemMockWithProduct($id, $product, $price, $qty);
    }

    private function buildQuoteItemMockWithProduct(int $id, ProductInterface $product, float $price, float $qty): QuoteItem
    {
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getQty', 'getProduct'])
            ->addMethods(['getPriceInclTax'])
            ->getMock();
        $item->method('getItemId')->willReturn($id);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getQty')->willReturn($qty);
        $item->method('getProduct')->willReturn($product);
        return $item;
    }
}
