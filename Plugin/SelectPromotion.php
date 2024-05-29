<?php

namespace Taggrs\DataLayer\Plugin;

use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Taggrs\DataLayer\Api\DataLayerInterface;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class SelectPromotion implements DataLayerInterface
{
    private Session $session;

    private UserDataHelper $userDataHelper;

    private QuoteDataHelper $quoteDataHelper;


    private string $couponCode = '';

    private int $couponId = 0;

    public function __construct(
        Session $session,
        UserDataHelper $userDataHelper,
        QuoteDataHelper $quoteDataHelper,
    )
    {
        $this->session = $session;
        $this->userDataHelper = $userDataHelper;
        $this->quoteDataHelper = $quoteDataHelper;
    }

    public function afterExecute(CouponPost $subject, Redirect $result)
    {
        $request = $subject->getRequest();

        if ($request->getParam('remove') == 0 && $request->getParam('coupon_code') !== null) {

            $coupon = $this->quoteDataHelper->getCouponByCode($request->getParam('coupon_code'));
            $this->couponCode = $coupon->getCode();
            $this->couponId = $coupon->getId();
            $dataLayer = $this->getDataLayer();
            $this->session->setDataLayer($dataLayer);

        }

        return $result;
    }

    public function getEvent(): string
    {
        return 'select_promotion';
    }

    public function getEcommerce(): array
    {
        return [
            'promotion_id' => $this->couponId,
            'promotion_name' => $this->couponCode,
            'items' => $this->quoteDataHelper->getItemsFromQuote(true, true)
        ];
    }

    public function getUserData(): array
    {
        return $this->userDataHelper->getUserData();
    }

    public function getDataLayer(): array
    {
        return [
            'event' => $this->getEvent(),
            'ecommerce' => $this->getEcommerce(),
//            'user_data' => $this->getUserData(),
        ];
    }
}
