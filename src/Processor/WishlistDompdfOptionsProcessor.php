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

namespace Sylius\WishlistPlugin\Processor;

use Dompdf\Options;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;

final class WishlistDompdfOptionsProcessor implements OptionsProcessorInterface
{
    public function process(object $generator, string $context = 'default'): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('defaultPaperSize', 'a4');
        $options->set('defaultPaperOrientation', 'portrait');

        /** @var \Dompdf\Dompdf $generator */
        $generator->setOptions($options);
    }
}
