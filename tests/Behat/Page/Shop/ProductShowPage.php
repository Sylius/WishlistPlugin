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

use Behat\Mink\Exception\ElementNotFoundException;
use Sylius\Behat\Page\Shop\Product\ShowPage;

class ProductShowPage extends ShowPage implements ProductShowPageInterface
{
    public function addVariantToWishlist(): void
    {
        $wishlistButton = $this->getElement('wishlist_button');
        $addButton = $wishlistButton->find('css', '[data-test-wishlist-add-product]');

        if (null === $addButton) {
            throw new ElementNotFoundException($this->getDriver());
        }

        $addButton->click();

        $this->waitForWishlistButtonUpdate();
    }

    public function hasFilledWishlistHeart(): bool
    {
        $this->waitForWishlistButtonUpdate();

        $addButton = $this->getElement('wishlist_button')->find('css', '[data-test-wishlist-add-product]');

        if (null === $addButton) {
            return false;
        }

        return 'remove' === $addButton->getAttribute('data-live-action-param');
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'wishlist_button' => '[data-test-wishlist-button]',
        ]);
    }

    private function waitForWishlistButtonUpdate(): void
    {
        $liveComponent = $this->getElement('wishlist_button');

        usleep(500000);
        $liveComponent->waitFor(5000, fn () => !$liveComponent->hasAttribute('busy'));
    }
}
