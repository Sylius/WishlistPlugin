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

namespace Sylius\WishlistPlugin\CommandHandler\Wishlist;

use Gedmo\Exception\UploadableInvalidMimeTypeException;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\ImportWishlistFromCsv;
use Sylius\WishlistPlugin\Controller\Action\AddProductVariantToWishlistAction;
use Sylius\WishlistPlugin\Factory\CsvSerializerFactoryInterface;
use Sylius\WishlistPlugin\Model\DTO\CsvWishlistProduct;
use Sylius\WishlistPlugin\Model\DTO\CsvWishlistProductInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class ImportWishlistFromCsvHandler
{
    public function __construct(
        private AddProductVariantToWishlistAction $addProductVariantToWishlistAction,
        private ProductVariantRepositoryInterface $productVariantRepository,
        private array $allowedMimeTypes,
        private CsvSerializerFactoryInterface $csvSerializerFactory,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(ImportWishlistFromCsv $importWishlistFromCsv): Response
    {
        $fileInfo = $importWishlistFromCsv->getFileInfo();
        $request = $importWishlistFromCsv->getRequest();
        $wishlistId = $importWishlistFromCsv->getWishlistId();

        $this->getDataFromFile($fileInfo, $request);

        return $this->addProductVariantToWishlistAction->__invoke($wishlistId, $request);
    }

    private function getDataFromFile(\SplFileInfo $fileInfo, Request $request): void
    {
        if (!$this->fileIsValidMimeType($fileInfo)) {
            throw new UploadableInvalidMimeTypeException();
        }

        $csvData = file_get_contents((string) $fileInfo);

        $csvWishlistProducts = $this->csvSerializerFactory->createNew()->deserialize($csvData, sprintf('%s[]', CsvWishlistProduct::class), 'csv', [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            CsvEncoder::AS_COLLECTION_KEY => true,
        ]);

        $variantIdRequestAttributes = [];

        /** @var CsvWishlistProduct $csvWishlistProduct */
        foreach ($csvWishlistProducts as $csvWishlistProduct) {
            if ($this->csvWishlistProductIsValid($csvWishlistProduct)) {
                $variantIdRequestAttributes[] = $csvWishlistProduct->getVariantId();
                $request->attributes->set('variantId', $variantIdRequestAttributes);
            } else {
                /** @var Session $session */
                $session = $this->requestStack->getSession();

                $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.csv_file_contains_incorrect_products'));
            }
        }
    }

    private function fileIsValidMimeType(\SplFileInfo $fileInfo): bool
    {
        $finfo = new \finfo(\FILEINFO_MIME_TYPE);

        return in_array($finfo->file($fileInfo->getRealPath()), $this->allowedMimeTypes, true);
    }

    private function csvWishlistProductIsValid(CsvWishlistProductInterface $csvWishlistProduct): bool
    {
        $wishlistProduct = $this->productVariantRepository->findOneBy([
            'id' => $csvWishlistProduct->getVariantId(),
            'product' => $csvWishlistProduct->getProductId(),
            'code' => $csvWishlistProduct->getVariantCode(),
        ]);

        if (null === $wishlistProduct) {
            return false;
        }

        return true;
    }
}
