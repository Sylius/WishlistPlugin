<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Resolver\TokenUserResolver;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TokenUserResolverTest extends TestCase
{
    private TokenUserResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TokenUserResolver();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(TokenUserResolver::class, $this->resolver);
    }

    public function testShouldImplementTokenUserResolverInterface(): void
    {
        $this->assertInstanceOf(TokenUserResolverInterface::class, $this->resolver);
    }

    public function testShouldReturnNullForNullToken(): void
    {
        $this->assertNull($this->resolver->resolve(null));
    }

    public function testShouldReturnUserForNonAnonymousToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $token->expects($this->once())->method('getUser')->willReturn($user);

        $this->assertSame(
            $user,
            $this->resolver->resolve($token),
        );
    }
}
