<?php

/*
 * This file is part of the Sylius CMS Plugin package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $env = $_ENV['APP_ENV'] ?? 'dev';

    if (str_starts_with($env, 'test')) {
        // Symfony 8 dropped XmlFileLoader from the default kernel loader resolver, so Sylius core
        // ships a services.php equivalent starting with 2.3 — fall back to the .xml for older Sylius versions.
        $behatServicesPhp = __DIR__ . '/../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.php';
        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.' . (is_file($behatServicesPhp) ? 'php' : 'xml'));
        $container->import('@SyliusWishlistPlugin/tests/Behat/Resources/services.yml');
    }

    if (!filter_var($_ENV['TEST_SYLIUS_WISHLIST_PDF_LEGACY'] ?? 'true', FILTER_VALIDATE_BOOLEAN)) {
        $container->extension('sylius_wishlist_plugin', [
            'pdf_generator' => ['legacy' => false],
        ]);
    }
};
