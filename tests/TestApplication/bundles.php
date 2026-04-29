<?php

declare(strict_types=1);

$bundles = [
    Sylius\PdfGenerationBundle\SyliusPdfGenerationBundle::class => ['all' => true],
    Sylius\WishlistPlugin\SyliusWishlistPlugin::class => ['all' => true],
];

return $bundles;
