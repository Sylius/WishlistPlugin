<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\Actions;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\WishlistPlugin\Command\Wishlist\CreateNewWishlist;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CreateNewWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public bool $showModal = false;

    #[LiveProp(writable: true)]
    public string $name = '';

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly ChannelContextInterface $channelContext,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->name = '';
    }

    #[LiveAction]
    public function create(): ?RedirectResponse
    {
        $wishlistName = trim($this->name);

        if ($wishlistName === '') {
            return null;
        }

        try {
            try {
                $channel = $this->channelContext->getChannel();
                $channelCode = $channel->getCode();
            } catch (ChannelNotFoundException) {
                $channelCode = null;
            }

            $createNewWishlist = new CreateNewWishlist($wishlistName, $channelCode);
            $envelope = $this->commandBus->dispatch($createNewWishlist);
            /** @var HandledStamp $handled */
            $handled = $envelope->last(HandledStamp::class);
            $createdWishlistId = $handled->getResult();

            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.create_new_wishlist'));

            return new RedirectResponse(
                $this->urlGenerator->generate(
                    'sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist',
                    ['wishlistId' => $createdWishlistId],
                ),
            );
        } catch (HandlerFailedException) {
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_already_exists'));

            return null;
        }
    }
}
