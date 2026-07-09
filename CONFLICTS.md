- `api-platform/symfony:4.3.16`:

  This version registers the `api_platform.openapi.name_converter` service as a child of
  `serializer.name_converter.metadata_aware.abstract`, which does not exist on Symfony 6.4. Container compilation then
  fails in `ResolveChildDefinitionsPass` with *Parent definition "serializer.name_converter.metadata_aware.abstract" does
  not exist*, so the application cannot boot and every `bin/console` command and the whole test suite break.
  The conflict is temporary and keeps the integration on 4.3.15 until a fixed `api-platform/symfony` release is tagged.

  References: https://github.com/api-platform/core/pull/8386
