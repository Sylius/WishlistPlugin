<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\CollectiveActions;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CopyToWishlistComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;
    /** @var array<int, array{name: string, id: int}> */
    #[LiveProp]
    public array $wishlists = [];

    #[LiveProp]
    public bool $showModal = false;
    #[LiveProp(writable: true)]
    public ?int $selectedId = null;

    #[LiveAction]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedId = null;
    }
}
