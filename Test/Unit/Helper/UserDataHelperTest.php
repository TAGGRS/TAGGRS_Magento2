<?php

namespace Taggrs\DataLayer\Test\Unit\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Helper\UserDataHelper;

class UserDataHelperTest extends TestCase
{
    private CustomerSession $customerSession;
    private CheckoutSession $checkoutSession;
    private UserDataHelper $helper;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getCustomer'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();

        $this->helper = new UserDataHelper($this->customerSession, $this->checkoutSession);
    }

    public function testGetUserDataReturnsEmailAndHashWhenLoggedIn(): void
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->addMethods(['getEmail'])->getMock();
        $customer->method('getEmail')->willReturn('customer@example.com');

        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn($customer);

        $result = $this->helper->getUserData();

        $this->assertSame('customer@example.com', $result['email']);
        $this->assertSame(hash('sha256', 'customer@example.com'), $result['email_hashed']);
    }

    public function testGetUserDataReturnsEmptyArrayWhenLoggedInButEmailIsNull(): void
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->addMethods(['getEmail'])->getMock();
        $customer->method('getEmail')->willReturn(null);

        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn($customer);

        $this->assertSame([], $this->helper->getUserData());
    }

    public function testGetUserDataReturnsGuestEmailFromBillingAddress(): void
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail'])
            ->getMock();
        $address->method('getEmail')->willReturn('guest@example.com');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->helper->getUserData();

        $this->assertSame('guest@example.com', $result['email']);
        $this->assertSame(hash('sha256', 'guest@example.com'), $result['email_hashed']);
    }

    public function testGetUserDataReturnsEmptyWhenGuestAndNoBillingEmail(): void
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail'])
            ->getMock();
        $address->method('getEmail')->willReturn(null);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->assertSame([], $this->helper->getUserData());
    }

    public function testGetUserDataReturnsEmptyWhenCheckoutSessionThrowsNoSuchEntityException(): void
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutSession->method('getQuote')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame([], $this->helper->getUserData());
    }

    public function testGetUserDataReturnsEmptyWhenCheckoutSessionThrowsLocalizedException(): void
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutSession->method('getQuote')
            ->willThrowException(new LocalizedException(__('Error')));

        $this->assertSame([], $this->helper->getUserData());
    }

    public function testEmailHashIsSha256(): void
    {
        $email = 'test@test.nl';
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->addMethods(['getEmail'])->getMock();
        $customer->method('getEmail')->willReturn($email);

        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn($customer);

        $result = $this->helper->getUserData();

        $this->assertSame(hash('sha256', $email), $result['email_hashed']);
    }
}
