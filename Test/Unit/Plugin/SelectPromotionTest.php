<?php

namespace Taggrs\DataLayer\Test\Unit\Plugin;

use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;
use Taggrs\DataLayer\Plugin\SelectPromotion;

class SelectPromotionTest extends TestCase
{
    private Session $customerSession;
    private UserDataHelper $userDataHelper;
    private QuoteDataHelper $quoteDataHelper;
    private SelectPromotion $plugin;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['setDataLayer'])
            ->getMock();

        $this->userDataHelper  = $this->createMock(UserDataHelper::class);
        $this->quoteDataHelper = $this->createMock(QuoteDataHelper::class);

        $this->userDataHelper->method('getUserData')->willReturn([]);

        $this->plugin = new SelectPromotion(
            $this->customerSession,
            $this->userDataHelper,
            $this->quoteDataHelper
        );
    }

    public function testGetEventReturnsSelectPromotion(): void
    {
        $this->assertSame('select_promotion', $this->plugin->getEvent());
    }

    public function testAfterExecuteDoesNothingWhenRemoveFlagIsSet(): void
    {
        $subject = $this->buildSubject(1, 'SAVE20');
        $result  = $this->createMock(Redirect::class);

        $this->customerSession->expects($this->never())->method('setDataLayer');

        $returned = $this->plugin->afterExecute($subject, $result);

        $this->assertSame($result, $returned);
    }

    public function testAfterExecuteDoesNothingWhenCouponCodeIsNull(): void
    {
        $subject = $this->buildSubject(0, null);
        $result  = $this->createMock(Redirect::class);

        $this->customerSession->expects($this->never())->method('setDataLayer');

        $this->plugin->afterExecute($subject, $result);
    }

    public function testAfterExecuteDoesNothingWhenCouponNotFound(): void
    {
        $coupon = $this->createMock(\Magento\SalesRule\Model\Coupon::class);
        $coupon->method('getId')->willReturn(null);
        $this->quoteDataHelper->method('getCouponByCode')->with('INVALID')->willReturn($coupon);

        $subject = $this->buildSubject(0, 'INVALID');
        $result  = $this->createMock(Redirect::class);

        $this->customerSession->expects($this->never())->method('setDataLayer');

        $this->plugin->afterExecute($subject, $result);
    }

    public function testAfterExecuteSetsDataLayerWhenValidCouponApplied(): void
    {
        $coupon = $this->createMock(\Magento\SalesRule\Model\Coupon::class);
        $coupon->method('getId')->willReturn(55);
        $this->quoteDataHelper->method('getCouponByCode')->with('SAVE20')->willReturn($coupon);

        $subject = $this->buildSubject(0, 'SAVE20');
        $result  = $this->createMock(Redirect::class);

        $this->customerSession->expects($this->once())->method('setDataLayer');

        $this->plugin->afterExecute($subject, $result);
    }

    public function testGetEcommerceContainsPromotionIdAndName(): void
    {
        $coupon = $this->createMock(\Magento\SalesRule\Model\Coupon::class);
        $coupon->method('getId')->willReturn(55);
        $this->quoteDataHelper->method('getCouponByCode')->willReturn($coupon);

        $subject = $this->buildSubject(0, 'SAVE20');
        $result  = $this->createMock(Redirect::class);
        $this->plugin->afterExecute($subject, $result);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertSame(55, $ecommerce['promotion_id']);
        $this->assertSame('SAVE20', $ecommerce['promotion_name']);
    }

    public function testGetEcommerceContainsItemsFromQuoteDataHelper(): void
    {
        $items = [['item_id' => 'X', 'item_name' => 'Product']];
        $this->quoteDataHelper->method('getItemsFromQuote')->with(true, true)->willReturn($items);

        $coupon = $this->createMock(\Magento\SalesRule\Model\Coupon::class);
        $coupon->method('getId')->willReturn(55);
        $this->quoteDataHelper->method('getCouponByCode')->willReturn($coupon);

        $subject = $this->buildSubject(0, 'SAVE20');
        $result  = $this->createMock(Redirect::class);
        $this->plugin->afterExecute($subject, $result);

        $ecommerce = $this->plugin->getEcommerce();

        $this->assertSame($items, $ecommerce['items']);
    }

    public function testAfterExecuteReturnsOriginalRedirectResult(): void
    {
        $subject = $this->buildSubject(0, null);
        $result  = $this->createMock(Redirect::class);

        $returned = $this->plugin->afterExecute($subject, $result);

        $this->assertSame($result, $returned);
    }

    private function buildSubject(int $remove, ?string $couponCode): CouponPost
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnMap([
            ['remove', null, $remove],
            ['coupon_code', null, $couponCode],
        ]);

        $subject = $this->getMockBuilder(CouponPost::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();
        $subject->method('getRequest')->willReturn($request);

        return $subject;
    }
}
