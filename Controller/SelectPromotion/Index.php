<?php

namespace Taggrs\DataLayer\Controller\SelectPromotion;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Taggrs\DataLayer\Controller\AbstractDataLayerController;
use Taggrs\DataLayer\Helper\QuoteDataHelper;
use Taggrs\DataLayer\Helper\UserDataHelper;

class Index extends AbstractDataLayerController
{
    private QuoteDataHelper $quoteDataHelper;


    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteDataHelper $quoteDataHelper,
        UserDataHelper $userDataHelper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $resultJsonFactory, $userDataHelper, $storeManager);

        $this->quoteDataHelper = $quoteDataHelper;
    }

    public function getEvent(): string
    {
        return 'select_promotion';
    }

    public function getEcommerce(): array
    {
        $ecommerce = [
            'currency' => $this->getCurrency(),
            'items' => $this->quoteDataHelper->getItemsFromQuote(true, true)
        ];

        $coupon = $this->quoteDataHelper->getCouponFromQuote();

        if ($coupon !== null) {
            $ecommerce['promotion_id'] = $coupon->getCouponId();
            $ecommerce['promotion_name'] = $coupon->getCode();
        }

        $ecommerce['user_data'] = $this->getUserData();

        return $ecommerce;
    }
}
