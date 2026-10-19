[Українська](README-UA.md)

# Release notes

What was added in every version and how to use it. Each minor version has its own page; each major has an overview with upgrade notes: [3.x](3.x/README.md) · [2.x](2.x/README.md) · [1.x](1.x/README.md).

Most historical git tags were not preserved. Versions are reconstructed from the `version` field of `composer.json` (present until 3.7.0) and the tags that still exist; "Released" is the date of the first commit of the minor version.

| Version | Released | Highlights |
| --- | --- | --- |
| [3.9](3.x/3.9/README.md) | 2026-10-14 | 3.9.2: immutable `RpcResponseContextBuilder`. 3.9.1: validator constraints cached per method, validation errors no longer swallowed. `#[RPC\APIGroups]` and `RpcAsyncRequest::$apiGroup` |
| [3.8](3.x/3.8/README.md) | 2026-09-25 | JSON-RPC notifications (`RpcNotificationRequest`), `RpcRequestFactory`, Symfony 8, `$rpc` validated inside `params.$rpc` |
| [3.7](3.x/3.7/README.md) | 2026-09-17 | Readonly `RpcAsyncRequest` with `meta`, `RpcTransport::SYNC_PREFIX` / `ASYNC_PREFIX`, `value_type` as a class name |
| [3.6](3.x/3.6/README.md) | 2026-05-15 | dto-transformer ^3, `DTOTransformerBundleBootTrait` (transformers booted from the container with a persistent cache) |
| [3.5](3.x/3.5/README.md) | 2026-02-22 | API versions: `#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`; `ResultAsDTO` and deprecated DTO/Param arguments removed |
| [3.4](3.x/3.4/README.md) | 2025-10-24 | `$rpc.ignoreCache`, `RpcResponse::toArray(ContextBuilderInterface)`, incoming `rayId` preserved |
| [3.3](3.x/3.3/README.md) | 2025-09-30 | `EnumNormalizer` and `DtoObjectNormalizer`, `Param(collection:)`, `#[RPC\ResultAsDTO]` deprecated, `$className` only with `AS_SMART` |
| [3.2](3.x/3.2/README.md) | 2025-09-29 | dto-transformer ^2, `#[RPC\DTOCollection]`, `context` options for `#[RPC\DTO]` / `#[RPC\Param]` |
| [3.1](3.x/3.1/README.md) | 2025-06-30 | `ConstraintsImposedException` moved to rpc-exceptions, `RpcResponse::getResult(): mixed` |
| [3.0](3.x/3.0/README.md) | 2025-05-10 | DTO layer extracted to `ufo-tech/dto-transformer` (`Ufo\DTO\…`), `#[RPC\Param]`, `#[RPC\CacheRelation]`, `AttributeTransformer`; `#[RPC\Response]` and `Ufo\RpcObject\Events` removed |
| [2.9](2.x/2.9/README.md) | 2025-05-09 | `renameKeys` in `#[RPC\DTO]`, DTO attributes on constructor parameters, `fromArray(..., $renameKey): static` |
| [2.8](2.x/2.8/README.md) | 2025-03-13 | `#[RPC\Lock]`, special params as one `$rpc` object with `rayId`, DTO validation via `#[RPC\Assertions]`, nested DTOs via `#[RPC\DTO]`, Symfony 7.2 |
| [2.7](2.x/2.7/README.md) | 2025-01-13 | `DTOTransformer` (DTO ↔ array) with `IArrayConvertible` / `IArrayConstructible` traits, callback URLs require a TLD, `ext-intl` required |
| [2.6](2.x/2.6/README.md) | 2024-07-24 | `#[RPC\IgnoreApi]`, PHPDoc/constructor type extraction and `array<string, Dto>` denormalization, callback URL checked and cached |
| [2.5](2.x/2.5/README.md) | 2024-07-14 | `#[RPC\ResultAsDTO]` for result schemas, `RpcResponseContextBuilder` / `RpcErrorNormalizer` in `Ufo\RpcObject\Transformer` |
| [2.4](2.x/2.4/README.md) | 2024-07-08 | Event classes for json-rpc-bundle 7, `RpcError::fromThrowable()`, `TypeHintResolver` |
| [2.3](2.x/2.3/README.md) | 2024-07-05 | `@FROM:<id>()` passes the whole parent result, `RpcTransport::getDomainUrl()` |
| [2.2](2.x/2.2/README.md) | 2024-07-02 | Serializer groups for results (`list`, `detail`, `group`…), UUID normalization |
| [2.1](2.x/2.1/README.md) | 2024-05-16 | `RpcAsyncRequest`, `RpcTransport` DSN with `#[Secret]` masking, Symfony constraints exported as `{class, context}`, PHP 8.3 |
| [2.0](2.x/2.0/README.md) | 2024-05-10 | `#[RPC\Cache]`, `RpcValidator` with per-parameter errors, PHP attributes instead of annotations, Symfony validator 7 |
| [1.5](1.x/1.5/README.md) | 2024-04-19 | Attributes moved to `RPC\*` (`RPC\Info` alias, `RPC\Response`, `RPC\Assertions`), dates normalized |
| [1.4](1.x/1.4/README.md) | 2023-02-01 | Initial public baseline: JSON-RPC 2.0 request/response model, batch with `@FROM:<id>(<field>)`, `$rpc.callback` / `$rpc.timeout`, `throwError()` |
