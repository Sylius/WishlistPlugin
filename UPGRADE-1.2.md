# UPGRADE FROM 1.1 TO 1.2

1. Support for the `SyliusPdfGenerationBundle` has been added as an alternative to the legacy PDF generation
   which was using `dompdf/dompdf` directly.
   To use it, set the `legacy` option to `false` in your configuration:

    ```yaml
    sylius_wishlist_plugin:
        pdf_generator:
            legacy: false
    ```

   The bundle is preconfigured with `dompdf` adapter and a `sylius_wishlist` context making it a drop-in replacement.

   The `sylius_wishlist_plugin.pdf_generator.legacy` option itself is deprecated and will be removed in 2.0,
   together with the entire legacy PDF generation path. The `SyliusPdfGenerationBundle` integration will become
   the only supported mode.

1. The following service now accepts new argument types from the `SyliusPdfGenerationBundle`. Passing the old types is deprecated and will be removed in 2.0:

   - `Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporter`:

     ```diff
     public function __construct(
     -   private Environment $twigRenderer,
     -   private DomPdfFactoryInterface $domPdfFactory,
     +   private Environment|TwigToPdfRendererInterface $twigRenderer,
     +   private ?DomPdfFactoryInterface $domPdfFactory = null,
     )
     ```

1. The following interfaces changed their method return type from `void` to `string`. If you have a custom implementation of either interface, update its signature accordingly:

   - `Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporterInterface`:

     ```diff
     -    public function export(Collection $data): void;
     +    public function export(Collection $data): string;
     ```

   - `Sylius\WishlistPlugin\Exporter\WishlistToPdfExporterInterface`:

     ```diff
     -    public function createModelToPdfAndExportToPdf(Collection $wishlistProducts): void;
     +    public function createModelToPdfAndExportToPdf(Collection $wishlistProducts): string;
     ```

   The methods now return the rendered PDF as a binary string instead of writing it directly to the response.

1. The following classes, interfaces, and services have been deprecated and will be removed in 2.0:

   | Deprecated                                                        | Replacement                                                           |
   |-------------------------------------------------------------------|-----------------------------------------------------------------------|
   | `Sylius\WishlistPlugin\Factory\DomPdfFactoryInterface`            | `Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface` |
   | `Sylius\WishlistPlugin\Factory\DomPdfFactory`                     | `Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface` |
   | `Sylius\WishlistPlugin\Factory\DomPdfOptionsFactoryInterface`     | `sylius/pdf-generation-bundle` option processors                      |
   | `Sylius\WishlistPlugin\Factory\DomPdfOptionsFactory`              | `sylius/pdf-generation-bundle` option processors                      |

   The corresponding services (`sylius_wishlist_plugin.custom_factory.dom_pdf` and `sylius_wishlist_plugin.custom_factory.dom_pdf_options`) are also deprecated.
