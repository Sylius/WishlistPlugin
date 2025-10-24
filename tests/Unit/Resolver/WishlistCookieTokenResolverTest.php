<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolver;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class WishlistCookieTokenResolverTest extends TestCase
{
    private MockObject&RequestStack $requestStack;

    private MockObject&Request $request;

    private WishlistCookieTokenResolver $resolver;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->resolver = new WishlistCookieTokenResolver(
            $this->requestStack,
            'token',
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistCookieTokenResolver::class, $this->resolver);
    }

    public function testShouldImplementWishlistCookieTokenResolverInterface(): void
    {
        $this->assertInstanceOf(WishlistCookieTokenResolverInterface::class, $this->resolver);
    }

    public function testShouldReturnTokenFromCookiesIfPresent(): void
    {
        $request = new Request();
        $request->cookies = new InputBag(['token' => 'cookie_token']);

        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn($request);

        $this->assertSame(
            'cookie_token',
            $this->resolver->resolve(),
        );
    }

    public function testShouldReturnTokenFromAttributesIfNotInCookies(): void
    {
        $request = new Request();
        $request->cookies = new InputBag();
        $request->attributes = new InputBag(['token' => 'attribute_token']);

        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn($request);

        $this->assertSame(
            'attribute_token',
            $this->resolver->resolve(),
        );
    }

    public function testShouldReturnNewTokenIfNotInCookiesNorAttribute(): void
    {
        $request = new Request();
        $request->cookies = new InputBag();
        $request->attributes = new InputBag();

        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn($request);

        $this->assertMatchesRegularExpression(
            "/^([a-f0-9\-]{36})$/",
            $this->resolver->resolve(),
        );
    }
}
