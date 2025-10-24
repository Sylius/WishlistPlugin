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

namespace Tests\Sylius\WishlistPlugin\Unit\Controller\Action;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Controller\Action\BaseWishlistsListingAction;
use Sylius\WishlistPlugin\Controller\Action\RenderHeaderTemplateAction;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class RenderHeaderTemplateActionTest extends TestCase
{
    private MockObject&Environment $twigEnvironment;

    private MockObject&WishlistsResolverInterface $wishlistsResolver;

    private RenderHeaderTemplateAction $action;

    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(Environment::class);
        $this->wishlistsResolver = $this->createMock(WishlistsResolverInterface::class);
        $this->action = new RenderHeaderTemplateAction(
            $this->twigEnvironment,
            $this->wishlistsResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RenderHeaderTemplateAction::class, $this->action);
    }

    public function testShouldExtendBaseWishlistsListingAction(): void
    {
        $this->assertInstanceOf(BaseWishlistsListingAction::class, $this->action);
    }

    public function testShouldRenderHeaderTemplate(): void
    {
        $this->wishlistsResolver->expects($this->once())->method('resolve')->willReturn([]);
        $this->twigEnvironment->expects($this->once())->method('render')->with('@SyliusWishlistPlugin/common/widget.html.twig', ['wishlists' => []])->willReturn('TEMPLATE');

        $this->assertInstanceOf(
            Response::class,
            ($this->action)($this->createMock(Request::class)),
        );
    }
}
