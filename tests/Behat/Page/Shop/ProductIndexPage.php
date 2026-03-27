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

namespace Tests\Sylius\WishlistPlugin\Behat\Page\Shop;

use Sylius\Behat\Page\Shop\Product\IndexPage;

class ProductIndexPage extends IndexPage implements ProductIndexPageInterface
{
    public function addProductToWishlist(string $productName): void
    {
        $this->getSession()->setCookie('MOCKSESSID', 'foo');

        $wishlistButton = $this->getElement('wishlist_button', ['%product_name%' => $productName]);
        $addButton = $wishlistButton->find('css', '[data-test-wishlist-add-product]');
        $addButton->click();

        $this->waitForWishlistButtonUpdate($productName);
    }

    public function hasFilledWishlistHeart(): bool
    {
        $this->waitForWishlistButtonUpdateAny();

        $addButton = $this->getElement('wishlist_button_any')->find('css', '[data-test-wishlist-add-product]');

        if (null === $addButton) {
            return false;
        }

        return 'remove' === $addButton->getAttribute('data-live-action-param');
    }

    public function hasFilledWishlistHeartForProduct(string $productName): bool
    {
        $this->waitForWishlistButtonUpdate($productName);

        $addButton = $this->getElement('wishlist_button', ['%product_name%' => $productName])
            ->find('css', '[data-test-wishlist-add-product]');

        if (null === $addButton) {
            return false;
        }

        return 'remove' === $addButton->getAttribute('data-live-action-param');
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'wishlist_button' => '[data-test-wishlist-button="%product_name%"]',
            'wishlist_button_any' => '[data-test-wishlist-button]',
        ]);
    }

    private function waitForWishlistButtonUpdate(string $productName): void
    {
        usleep(500000);
        $this->getElement('wishlist_button', ['%product_name%' => $productName])
            ->waitFor(5000, fn ($element) => !$element->hasAttribute('busy'));
    }

    private function waitForWishlistButtonUpdateAny(): void
    {
        usleep(500000);
        $this->getElement('wishlist_button_any')
            ->waitFor(5000, fn ($element) => !$element->hasAttribute('busy'));
    }
}
