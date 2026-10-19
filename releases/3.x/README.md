[Українська](README-UA.md)

# ufo-tech/rpc-objects 3.x

Major 3 moved the DTO layer into a separate package, `ufo-tech/dto-transformer`, and dropped the event classes, which now live in json-rpc-bundle 8+. What remains here is the JSON-RPC object model, RPC attributes and the serializer setup. Over the 3.x line the package added:
- `#[RPC\Param]`, `#[RPC\CacheRelation]`, `#[RPC\DTOCollection]` and `AttributeTransformer`
- `EnumNormalizer` / `DtoObjectNormalizer` and a reworked serializer pipeline
- `$rpc.ignoreCache` and `RpcResponse::toArray(ContextBuilderInterface)`
- API versions (`#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`)
- `DTOTransformerBundleBootTrait` to boot the transformers from the container
- JSON-RPC notifications (`RpcNotificationRequest`) and `RpcRequestFactory`
- Symfony 8 support
- API groups (`#[RPC\APIGroups]`)

## Requirements

| | 3.0.1 | 3.9.2 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2\|^8.0` (since 3.8) |
| `symfony/property-info` | — | `^7.2\|^8.0` |
| `symfony/http-client`, `symfony/property-access` | `*` | `*` |
| `ufo-tech/rpc-exceptions` | `*` | `^2.0.1` (since 3.8) |
| `ufo-tech/dto-transformer` | `^1.0` | `^3.1` (`^2.0` in 3.2–3.5, `^3.0` since 3.6) |
| Other | `ext-intl` | `ext-intl` |

## Versions

| Version | Highlights |
|---|---|
| [3.0](3.0/README.md) | DTO layer extracted to `ufo-tech/dto-transformer` (`Ufo\DTO\…`), `#[RPC\Param]`, `#[RPC\CacheRelation]`, `AttributeTransformer`; `#[RPC\Response]` and `Ufo\RpcObject\Events` removed |
| [3.1](3.1/README.md) | `ConstraintsImposedException` moved to rpc-exceptions, `RpcResponse::getResult(): mixed`, short-lived `DocsHelper` (added in 3.1.0, removed in 3.1.2) |
| [3.2](3.2/README.md) | dto-transformer ^2, `#[RPC\DTOCollection]`, `context` options for `#[RPC\DTO]` / `#[RPC\Param]` |
| [3.3](3.3/README.md) | `EnumNormalizer` and `DtoObjectNormalizer`, `Param(collection:)`, `#[RPC\ResultAsDTO]` deprecated, `$className` only with `AS_SMART` |
| [3.4](3.4/README.md) | `$rpc.ignoreCache`, `RpcResponse::toArray(ContextBuilderInterface)`, incoming `rayId` preserved |
| [3.5](3.5/README.md) | API versions: `#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`; `ResultAsDTO` and deprecated DTO/Param arguments removed |
| [3.6](3.6/README.md) | dto-transformer ^3, `DTOTransformerBundleBootTrait` (transformers booted from the container with a persistent cache) |
| [3.7](3.7/README.md) | Readonly `RpcAsyncRequest` with `meta`, `RpcTransport::SYNC_PREFIX` / `ASYNC_PREFIX`, `value_type` as a class name |
| [3.8](3.8/README.md) | JSON-RPC notifications (`RpcNotificationRequest`), `RpcRequestFactory`, Symfony 8, `$rpc` validated inside `params.$rpc` |
| [3.9](3.9/README.md) | `#[RPC\APIGroups]` and `RpcAsyncRequest::$apiGroup`; 3.9.1 caches validator constraints and stops swallowing errors; 3.9.2 immutable `RpcResponseContextBuilder` |

## Upgrading from 2.x

1. **Add `ufo-tech/dto-transformer`** (`^1.0` for 3.0) and update imports:

   | Before | After |
   | --- | --- |
   | `Ufo\RpcObject\DTO\DTOTransformer` | `Ufo\DTO\DTOTransformer` |
   | `Ufo\RpcObject\DTO\IArrayConstructible`, `IArrayConvertible` | `Ufo\DTO\Interfaces\…` |
   | `Ufo\RpcObject\DTO\ArrayConstructibleTrait`, `ArrayConvertibleTrait` | `Ufo\DTO\…` |
   | `Ufo\RpcObject\Helpers\TypeHintResolver` | `Ufo\DTO\Helpers\TypeHintResolver` |

   `DTOAttributesEnum` was removed.
2. **`#[RPC\Response]` was removed.** Use `#[RPC\ResultAsDTO]` (itself removed in 3.5 — see below).
3. **`Ufo\RpcObject\Events\*` was removed** (`RpcEvent`, `RpcPreExecuteEvent`, `RpcErrorEvent`, …). json-rpc-bundle 8 ships its own events.
4. **`RpcBatchRequest::addResult(array)`** → `addResponse(RpcResponse, array)`.
5. **`#[RPC\ResultAsDTO]`** targets methods only; use `#[RPC\DTO]` on properties and parameters.
6. **`Rules\Validator\Validator`** extends `Ufo\DTO\Helpers\Validator`; only `throw()` is left of its own API.
7. **Behaviour:** `RpcRequest` sorts params with `ksort`; `getSpecialParams()` always contains `rayId` (the request id by default); `RpcResponse::getResult()` got an `$asIs` flag.

## Breaking changes inside 3.x

This line shipped several breaking changes in minor and patch releases. Check them when moving across:
- **3.1.1:** `Ufo\RpcObject\Rules\Validator\ConstraintsImposedException` removed → `Ufo\RpcError\ConstraintsImposedException`. **3.1.2:** `DocsHelper` removed.
- **3.2:** `AttributeTransformer::fromArray()` got a `$namespaces` argument.
- **3.3:** `Param` got a new second positional argument `collection` — positional `default` calls break. In 3.3.3 `AS_SIMPLE_ARRAY` was renamed to `AS_SMART`.
- **3.4:** `SpecialRpcParams` is `readonly`; `IRpcSpecialParamHandler` gained `setParam()` / `resetParams()`; an incoming `rayId` wins over the request id.
- **3.5:** `ResultAsDTO` removed; `DTO` lost `renameKeys` / `transformerFQCN`, `Param` lost `default` / `convertorFQCN`; `RPC\Info::$alias` is required.
- **3.7:** `RpcAsyncRequest` is `readonly`; `getRpcRequest()` is deprecated.
- **3.8:** `RpcRequest::S_GROUP` changed from `'raw'` to the class name; id `0` is no longer regenerated; a request without `id` is a notification; `params: null`, `timeout: "30"` and `callback: null` are rejected.
- **3.9.1:** `RpcValidator::validateParam()` signature changed; a misconfigured `#[RPC\Assertions]` throws instead of silently skipping validation.
- **3.9.2:** `RpcResponseContextBuilder` is immutable — use the return value of `with*()` / `removeParent()`. See [3.9](3.9/README.md).
