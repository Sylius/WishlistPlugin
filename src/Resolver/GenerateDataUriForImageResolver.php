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

use Liip\ImagineBundle\Service\FilterService;
use Sylius\Component\Core\Model\ImageInterface;
use Symfony\Component\Asset\PackageInterface;
use Webmozart\Assert\Assert;

final readonly class GenerateDataUriForImageResolver implements GenerateDataUriForImageResolverInterface
{
    public function __construct(
        private PackageInterface $package,
        private FilterService $filterService,
        private string $imageFilterName,
    ) {
    }

    public function resolve(ImageInterface $image): string
    {
        Assert::notNull($image->getPath());
        $pathToReadFile = $this->package->getUrl($image->getPath());
        $targetUrl = $this->filterService->getUrlOfFilteredImage($pathToReadFile, $this->imageFilterName);
        $data = file_get_contents($targetUrl);
        $type = pathinfo($image->getPath(), \PATHINFO_EXTENSION);
        Assert::string($data);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    public function resolveWithNoImage(): string
    {
        $pathToReadFile = self::PATH_TO_EMPTY_PRODUCT_IMAGE;
        $data = file_get_contents($pathToReadFile);
        Assert::string($data);

        return 'data:image/png;base64,' . base64_encode($data);
    }
}
