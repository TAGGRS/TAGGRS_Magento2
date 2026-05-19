<?php

namespace Taggrs\DataLayer\Test\Unit\Model;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Taggrs\DataLayer\Model\PendingCookie;

class PendingCookieTest extends TestCase
{
    private CookieManagerInterface $cookieManager;
    private CookieMetadataFactory $metadataFactory;
    private PendingCookie $pendingCookie;

    protected function setUp(): void
    {
        $this->cookieManager   = $this->createMock(CookieManagerInterface::class);
        $this->metadataFactory = $this->createMock(CookieMetadataFactory::class);

        $this->pendingCookie = new PendingCookie($this->cookieManager, $this->metadataFactory);
    }

    public function testCookieNameConstant(): void
    {
        $this->assertSame('taggrs_ud_pending', PendingCookie::COOKIE_USER_DATA);
    }

    public function testSetUserDataPendingSetsPublicCookieWithCorrectNameAndValue(): void
    {
        $meta = $this->buildFluentPublicMeta();
        $this->metadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $this->cookieManager
            ->expects($this->once())
            ->method('setPublicCookie')
            ->with(PendingCookie::COOKIE_USER_DATA, '1', $meta);

        $this->pendingCookie->setUserDataPending();
    }

    public function testSetUserDataPendingUsesDefaultTtlOf300(): void
    {
        $meta = $this->buildFluentPublicMeta();
        $this->metadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $meta->expects($this->once())->method('setDuration')->with(300)->willReturnSelf();

        $this->pendingCookie->setUserDataPending();
    }

    public function testSetUserDataPendingAcceptsCustomTtl(): void
    {
        $meta = $this->buildFluentPublicMeta();
        $this->metadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $meta->expects($this->once())->method('setDuration')->with(600)->willReturnSelf();

        $this->pendingCookie->setUserDataPending(600);
    }

    public function testSetUserDataPendingSetsPathToRoot(): void
    {
        $meta = $this->buildFluentPublicMeta();
        $this->metadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $meta->expects($this->once())->method('setPath')->with('/')->willReturnSelf();

        $this->pendingCookie->setUserDataPending();
    }

    public function testSetUserDataPendingSetsSameSiteLax(): void
    {
        $meta = $this->buildFluentPublicMeta();
        $this->metadataFactory->method('createPublicCookieMetadata')->willReturn($meta);

        $meta->expects($this->once())->method('setSameSite')->with('Lax')->willReturnSelf();

        $this->pendingCookie->setUserDataPending();
    }

    public function testClearUserDataPendingCallsDeleteCookie(): void
    {
        $meta = $this->buildFluentCookieMeta();
        $this->metadataFactory->method('createCookieMetadata')->willReturn($meta);

        $this->cookieManager
            ->expects($this->once())
            ->method('deleteCookie')
            ->with(PendingCookie::COOKIE_USER_DATA, $meta);

        $this->pendingCookie->clearUserDataPending();
    }

    public function testClearUserDataPendingSetsPathOnMetadata(): void
    {
        $meta = $this->buildFluentCookieMeta();
        $this->metadataFactory->method('createCookieMetadata')->willReturn($meta);

        $meta->expects($this->once())->method('setPath')->with('/')->willReturnSelf();

        $this->pendingCookie->clearUserDataPending();
    }

    private function buildFluentPublicMeta(): PublicCookieMetadata
    {
        $meta = $this->createMock(PublicCookieMetadata::class);
        $meta->method('setPath')->willReturnSelf();
        $meta->method('setDuration')->willReturnSelf();
        $meta->method('setSameSite')->willReturnSelf();
        return $meta;
    }

    private function buildFluentCookieMeta(): CookieMetadata
    {
        $meta = $this->createMock(CookieMetadata::class);
        $meta->method('setPath')->willReturnSelf();
        return $meta;
    }
}
