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

namespace Sylius\WishlistPlugin\Resolver;

use Symfony\Component\HttpFoundation\Request;

final class RefererPathResolver implements RefererPathResolverInterface
{
    private const FALLBACK = '/';

    public function resolve(Request $request): string
    {
        $referer = (string) $request->headers->get('referer');
        $path = parse_url($referer, \PHP_URL_PATH);

        if (!is_string($path) || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return self::FALLBACK;
        }

        $query = parse_url($referer, \PHP_URL_QUERY);

        return is_string($query) && '' !== $query ? $path . '?' . $query : $path;
    }
}
