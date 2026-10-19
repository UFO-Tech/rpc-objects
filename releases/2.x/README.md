[Українська](README-UA.md)

# ufo-tech/rpc-objects 2.x

Major 2 replaced Doctrine annotations with native PHP attributes and moved to Symfony Validator 7. Most of the metadata the server reads today appeared in this line. Over the 2.x line the package added:
- `#[RPC\Cache]`, `#[RPC\IgnoreApi]`, `#[RPC\Lock]`, `#[RPC\ResultAsDTO]` and `#[RPC\DTO]`
- `RpcValidator` with per-parameter errors and Symfony constraints exported as `{class, context}`
- `RpcAsyncRequest` and the `RpcTransport` DSN with `#[Secret]` masking
- serializer groups for results and `RpcResponseContextBuilder` / `RpcErrorNormalizer` in `Ufo\RpcObject\Transformer`
- event classes for json-rpc-bundle 7 and `RpcError::fromThrowable()`
- `DTOTransformer` (DTO ↔ array) with DTO validation through `#[RPC\Assertions]`
- special parameters as one `$rpc` object with `rayId`

## Requirements

| | 2.0.1 | 2.9.0 |
|---|---|---|
| PHP | `>=8.2` | `>=8.3` (since 2.1.0) |
| Symfony | `validator ^7.0`, other `symfony/*` unconstrained | `serializer ^7.2`, `validator ^7.2` (since 2.8.6), other `symfony/*` unconstrained |
| Other | `ufo-tech/rpc-exceptions *` | adds `ext-intl` (since 2.7.3) |

Not declared in `require` but used by the code: `symfony/event-dispatcher-contracts` (events, 2.4–2.9), `symfony/http-foundation` (`RealUrlValidator`, since 2.6.4), `symfony/lock` (`RPC\Lock`, since 2.8.0). Before 2.5, `RpcResponse` also referenced classes from `ufo-tech/json-rpc-bundle`.

## Versions

| Version | Highlights |
|---|---|
| [2.0](2.0/README.md) | `#[RPC\Cache]`, `RpcValidator` with per-parameter errors, PHP attributes instead of annotations, Symfony validator 7 |
| [2.1](2.1/README.md) | `RpcAsyncRequest`, `RpcTransport` DSN with `#[Secret]` masking, Symfony constraints exported as `{class, context}`, PHP 8.3 |
| [2.2](2.2/README.md) | Serializer groups for results (`list`, `detail`, `group`…), assertion source text in `toArray()`, UUID normalization |
| [2.3](2.3/README.md) | `@FROM:<id>()` passes the whole parent result, `RpcTransport::getDomainUrl()` |
| [2.4](2.4/README.md) | Event classes for json-rpc-bundle 7 (`rpc.request` … `rpc.error`), `RpcError::fromThrowable()`, `TypeHintResolver` |
| [2.5](2.5/README.md) | `#[RPC\ResultAsDTO]` for result schemas (`#[RPC\Response]` deprecated), `RpcResponseContextBuilder` / `RpcErrorNormalizer` moved into `Ufo\RpcObject\Transformer` |
| [2.6](2.6/README.md) | `#[RPC\IgnoreApi]`, PHPDoc/constructor type extraction and `array<string, Dto>` denormalization, callback URL checked with `OPTIONS`/`POST` and cached |
| [2.7](2.7/README.md) | `DTOTransformer` (DTO ↔ array) with `IArrayConvertible` / `IArrayConstructible` traits, callback URLs require a TLD, `ext-intl` required |
| [2.8](2.8/README.md) | `#[RPC\Lock]`, special params as one `$rpc` object with `rayId`, DTO validation via `#[RPC\Assertions]`, nested DTOs via `#[RPC\DTO]`, readonly constructors, Symfony 7.2 |
| [2.9](2.9/README.md) | `renameKeys` in `#[RPC\DTO]`, DTO attributes on constructor parameters, `fromArray(..., $renameKey): static` |

## Upgrading from 1.x

1. **Attributes only.** Serializer annotations are no longer read; `doctrine/annotations` was dropped. Use PHP attributes.
2. **`RPC\Info`** lost `getAlias()` / `getConcat()` in favour of public readonly properties. `RPC\Info`, `RPC\Assertions` and `RPC\Cache` are `final readonly`; `RPC\Assertions` requires `$assertions`.
3. **Dependencies:** `symfony/validator ^7.0` from 2.0, PHP 8.3 from 2.1, `ext-intl` from 2.7.3, Symfony 7.2 from 2.8.6.

## Breaking changes inside 2.x

Several minors of this line changed public contracts. Check them when moving across:
- **2.1:** `RpcResponse::getResult()` return type changed from `mixed` to a union type. In 2.1.1–2.3.x the callback protocol rule accepted only `http`; `https` callbacks were rejected until 2.4.0.
- **2.2:** `RPC\Assertions::toArray()` returns `{constructor, payload}` instead of a flat list.
- **2.4:** `RPC\Response` constants are typed (`const string`).
- **2.5:** `RpcResponseContextBuilder` and `RpcErrorNormalizer` moved into `Ufo\RpcObject\Transformer`; `#[RPC\Response]` is deprecated in favour of `#[RPC\ResultAsDTO]`.
- **2.8:** special parameters are sent as one object `"$rpc": {"timeout": …}` instead of `"$rpc.timeout"` keys. `SpecialRpcParams::PREFIX`, `SpecialRpcParams::DEFAULT_TIMEOUT` and `SpecialRpcParams::fromArray()` were removed in favour of `SpecialRpcParamsEnum`. See [2.8](2.8/README.md).

Upgrading to 3.0: see [3.x](../3.x/README.md).
