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

namespace Sylius\WishlistPlugin\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @internal */
final class TwigHooksProfilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var string $env */
        $env = $container->getParameter('kernel.environment');
        if ($env === 'dev') {
            return;
        }

        if ($container->hasDefinition('sylius_twig_hooks.renderer.hook.profiler')) {
            $container->removeDefinition('sylius_twig_hooks.renderer.hook.profiler');
        }

        if ($container->hasDefinition('sylius_twig_hooks.renderer.hookable.profiler')) {
            $container->removeDefinition('sylius_twig_hooks.renderer.hookable.profiler');
        }
    }
}
