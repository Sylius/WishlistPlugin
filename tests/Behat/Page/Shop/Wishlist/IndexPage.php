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

namespace Tests\Sylius\WishlistPlugin\Behat\Page\Shop\Wishlist;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;
use Webmozart\Assert\Assert;

final class IndexPage extends SymfonyPage implements IndexPageInterface
{
    public function getRouteName(): string
    {
        return 'sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists';
    }

    public function addNewWishlist(): void
    {
        $this->getElement('add_new_wishlist')->click();
    }

    public function fillNewWishlistName(string $name): void
    {
        $this->getElement('new_wishlist_name')->setValue($name);
    }

    public function saveNewWishlist(): void
    {
        $this->getElement('save_new_wishlist')->click();
    }

    public function editWishlistName(string $wishlistName): void
    {
        $button = $this->getSession()->getPage()->find('css', '#wishlist-edit-button-' . $wishlistName);

        Assert::notNull($button, sprintf('There is no wishlist with name "%s" available to edit.', $wishlistName));

        $button->click();
    }

    public function fillEditWishlistName(string $newWishlistName): void
    {
        $this->getElement('edit_wishlist_name_input')->setValue($newWishlistName);
    }

    public function saveEditWishlist(): void
    {
        $this->getElement('save_edit_wishlist')->click();
    }

    protected function getDefinedElements(): array
    {
        return [
            'add_new_wishlist' => '[data-test-wishlist-add-new-wishlist]',
            'new_wishlist_name' => '#create_new_wishlist_name',
            'save_new_wishlist' => '#create_new_wishlist_save',
            'edit_wishlist_name' => '[data-test-wishlist-wishlist-edit]',
            'edit_wishlist_name_input' => '#edit_wishlist_name',
            'save_edit_wishlist' => '#edit_wishlist_save',
        ];
    }
}
