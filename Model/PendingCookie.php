<?php

namespace Taggrs\DataLayer\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;

class PendingCookie
{
    public const COOKIE_USER_DATA = 'taggrs_ud_pending';

    private CookieManagerInterface $cookieManager;
    private CookieMetadataFactory $cookieMetadataFactory;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    public function setUserDataPending( int $ttlSeconds = 300 ): void
    {
        $meta = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath( '/' )
            ->setDuration( $ttlSeconds )
            ->setSameSite( 'Lax' )
        ;

        $this->cookieManager->setPublicCookie( self::COOKIE_USER_DATA, '1', $meta );
    }

    public function clearUserDataPending(): void
    {
        $meta = $this->cookieMetadataFactory->createCookieMetadata()->setPath( '/' );
        $this->cookieManager->deleteCookie( self::COOKIE_USER_DATA, $meta );
    }
}
