<?php

namespace Taggrs\DataLayer\Controller\GetQuoteData;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Taggrs\DataLayer\Helper\QuoteDataHelper;

class Index implements HttpGetActionInterface
{
    private QuoteDataHelper $quoteDataHelper;

    private JsonFactory $jsonFactory;

    /**
     * @param QuoteDataHelper $quoteDataHelper
     * @param JsonFactory $jsonFactory
     */
    public function __construct(QuoteDataHelper $quoteDataHelper, JsonFactory $jsonFactory)
    {
        $this->quoteDataHelper = $quoteDataHelper;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $quoteData = $this->quoteDataHelper->getQuoteData();
        $result = $this->jsonFactory->create();
        $result->setData($quoteData);
        return  $result;
    }
}
