<?php

namespace Taggrs\DataLayer\Controller\GetSessionDataLayer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Index implements HttpGetActionInterface
{
    private Session $session;

    private JsonFactory $jsonFactory;

    private CookieManagerInterface $cookieManager;

    private CookieMetadataFactory $cookieMetadataFactory;

    public function __construct(
        Session $session,
        JsonFactory $jsonFactory,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->session               = $session;
        $this->jsonFactory           = $jsonFactory;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    public function execute()
    {
        $data = $this->session->getDataLayer();
        $this->session->unsDataLayer();

        $meta = $this->cookieMetadataFactory->createCookieMetadata()->setPath('/');
        $this->cookieManager->deleteCookie('taggrs_dl_pending', $meta);

        if (method_exists($this->session, 'writeClose')) {
            $this->session->writeClose();
        }

        return $this->jsonFactory->create()->setData($data ?: null);
    }
}
