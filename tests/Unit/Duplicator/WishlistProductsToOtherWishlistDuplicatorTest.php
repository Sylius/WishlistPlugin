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

namespace Tests\Sylius\WishlistPlugin\Unit\Duplicator;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Duplicator\WishlistProductsToOtherWishlistDuplicator;
use Sylius\WishlistPlugin\Duplicator\WishlistProductsToOtherWishlistDuplicatorInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WishlistProductsToOtherWishlistDuplicatorTest extends TestCase
{
    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&RequestStack $requestStack;

    private MockObject&TranslatorInterface $translator;

    private WishlistProductsToOtherWishlistDuplicator $duplicator;

    protected function setUp(): void
    {
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->duplicator = new WishlistProductsToOtherWishlistDuplicator(
            $this->wishlistProductFactory,
            $this->productVariantRepository,
            $this->wishlistRepository,
            $this->requestStack,
            $this->translator,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistProductsToOtherWishlistDuplicator::class, $this->duplicator);
    }

    public function testShouldImplementWishlistProductsToOtherWishlistDuplicatorInterface(): void
    {
        $this->assertInstanceOf(WishlistProductsToOtherWishlistDuplicatorInterface::class, $this->duplicator);
    }

    public function testShouldCopyWishlistProducts(): void
    {
        $firstVariant = $this->createMock(ProductVariantInterface::class);
        $secondVariant = $this->createMock(ProductVariantInterface::class);
        $firstWishlistProduct = $this->createMock(WishlistProductInterface::class);
        $secondWishlistProduct = $this->createMock(WishlistProductInterface::class);
        $destinedWishlist = $this->createMock(WishlistInterface::class);
        $this->productVariantRepository->expects($this->exactly(2))->method('find')->willReturnMap([['1', $firstVariant], ['24', $secondVariant]]);
        $destinedWishlist->expects($this->exactly(2))->method('hasProductVariant')->willReturnMap([[$firstVariant, false], [$secondVariant, false]]);
        $this->wishlistProductFactory->expects($this->exactly(2))->method('createForWishlistAndVariant')->willReturnMap([[$destinedWishlist, $firstVariant, $firstWishlistProduct], [$destinedWishlist, $secondVariant, $secondWishlistProduct]]);
        $destinedWishlist->expects($this->exactly(2))->method('addWishlistProduct');
        $this->wishlistRepository->expects($this->once())->method('add')->with($destinedWishlist);

        $this->duplicator->copyWishlistProductsToOtherWishlist(new ArrayCollection([
            ['variant' => '1'],
            ['variant' => '24'],
        ]), $destinedWishlist);
    }
}
