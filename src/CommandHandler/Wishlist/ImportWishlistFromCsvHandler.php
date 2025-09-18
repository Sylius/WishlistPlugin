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
use Sylius\Component\Core\Model\ProductVariantInterface;
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

        $file = new \SplFileObject($fileInfo->getRealPath(), 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        // Detect delimiter: try comma, then semicolon, then tab
        $file->setCsvControl(',');
        $headers = [];
        if (!$file->eof()) {
            $headers = $file->fgetcsv();
        }
        if (!\is_array($headers)) {
            $headers = [];
        }
        if (\count($headers) <= 1) {
            $file->rewind();
            $file->setCsvControl(';');
            $headers = $file->fgetcsv();
            if (!\is_array($headers)) { $headers = []; }
        }
        if (\count($headers) <= 1) {
            $file->rewind();
            $file->setCsvControl("\t");
            $headers = $file->fgetcsv();
            if (!\is_array($headers)) { $headers = []; }
        }
        $map = [];
        foreach ($headers as $idx => $name) {
            $key = strtolower(trim((string) $name));
            if ($key !== '') {
                $map[$key] = (int) $idx;
            }
        }
        // expected keys from export
        $keyVariantId = $map['variantid'] ?? null;
        $keyProductId = $map['productid'] ?? null;
        $keyVariantCode = $map['variantcode'] ?? null;

        $variantIdRequestAttributes = [];
        while (!$file->eof()) {
            $row = $file->fgetcsv();
            if (!\is_array($row) || $row === [null] || $row === false) {
                continue;
            }
            $variantId = $keyVariantId !== null ? ($row[$keyVariantId] ?? null) : null;
            $productId = $keyProductId !== null ? ($row[$keyProductId] ?? null) : null;
            $variantCode = $keyVariantCode !== null ? ($row[$keyVariantCode] ?? null) : null;

            $variantId = is_string($variantId) ? trim($variantId) : $variantId;
            $productId = is_string($productId) ? trim($productId) : $productId;
            $variantCode = is_string($variantCode) ? trim($variantCode) : $variantCode;

            $variantId = (is_numeric($variantId)) ? (int) $variantId : null;
            $productId = (is_numeric($productId)) ? (int) $productId : ($productId !== null && $productId !== '' ? (int) $productId : null);
            $variantCode = is_string($variantCode) ? $variantCode : null;

            if ($variantId === null && $productId === null && ($variantCode === null || $variantCode === '')) {
                continue;
            }

            $dto = new CsvWishlistProduct();
            $dto->setVariantId($variantId);
            $dto->setProductId($productId);
            $dto->setVariantCode($variantCode);

            $variant = $this->resolveVariant($dto);
            if ($variant instanceof ProductVariantInterface) {
                $variantIdRequestAttributes[] = (int) $variant->getId();
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

    private function resolveVariant(CsvWishlistProductInterface $csvWishlistProduct): ?ProductVariantInterface
    {
        // Prefer strong lookup by variant ID when present
        $variantId = $csvWishlistProduct->getVariantId();
        if (null !== $variantId) {
            /** @var ProductVariantInterface|null $variant */
            $variant = $this->productVariantRepository->find($variantId);
            if (null !== $variant) {
                // Accept by ID alone to ensure exported files always import
                return $variant;
            }
        }

        // Fallback: resolve by unique variant code
        $code = $csvWishlistProduct->getVariantCode();
        if (null !== $code && $code !== '') {
            /** @var ProductVariantInterface|null $variant */
            $variant = $this->productVariantRepository->findOneBy(['code' => (string) $code]);
            if (null !== $variant) {
                return $variant;
            }
        }

        return null;
    }
}
