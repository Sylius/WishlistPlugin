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

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\ResourceActions;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductVariantFromWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductVariantFromWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductVariantNotFoundException;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class RemoveProductVariantFromWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&RepositoryInterface $wishlistProductRepository;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&AuthorizationCheckerInterface $authorizationChecker;

    private MockObject&ProductVariantInterface $productVariant;

    private RemoveProductVariantFromWishlist $command;

    private RemoveProductVariantFromWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistProductRepository = $this->createMock(RepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->command = new RemoveProductVariantFromWishlist(1, 'wishlist_token');
        $this->handler = new RemoveProductVariantFromWishlistHandler(
            $this->wishlistRepository,
            $this->productVariantRepository,
            $this->wishlistProductRepository,
            $this->wishlistManager,
            $this->authorizationChecker,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveProductVariantFromWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveProductVariantFromWishlist(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);

        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn($this->productVariant);
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('wishlist_token')->willReturn($wishlist);
        $this->wishlistProductRepository->expects($this->once())->method('findOneBy')->with(['variant' => $this->productVariant, 'wishlist' => $wishlist])->willReturn($wishlistProduct);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->with(ResourceActions::DELETE, $wishlist)->willReturn(true);
        $wishlist->expects($this->once())->method('removeProduct')->with($wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('flush');

        $this->assertSame(
            $wishlist,
            ($this->handler)($this->command),
        );
    }

    public function testShouldThrowExceptionWhenProductVariantNotFound(): void
    {
        $this->expectException(ProductVariantNotFoundException::class);

        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionWhenWishlistNotFound(): void
    {
        $this->expectException(WishlistNotFoundException::class);

        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn($this->productVariant);
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('wishlist_token')->willReturn(null);

        ($this->handler)($this->command);
    }
}
