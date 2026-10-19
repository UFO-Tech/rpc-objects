[Українська](README-UA.md)

# 3.0 — DTO layer extracted to `ufo-tech/dto-transformer`, `#[RPC\Param]`, `#[RPC\CacheRelation]`

Versions: 3.0.0, 3.0.1. First released: 2025-05-10.

## Highlights

- The DTO toolkit (`DTOTransformer`, array interfaces and traits, `TypeHintResolver`, the base validator) moved to the new package `ufo-tech/dto-transformer ^1.0` under `Ufo\DTO\…`.
- `RPC\DTO`, `RPC\Assertions` and `Rules\Validator\Validator` now extend the `ufo-tech/dto-transformer` classes; `AttributeTransformer` hydrates RPC attributes from arrays.
- New attributes `#[RPC\Param]` (scalar types for converted parameters) and `#[RPC\CacheRelation]` (related cache invalidation / warm-up).
- Removed: the deprecated `#[RPC\Response]` and the `Ufo\RpcObject\Events` namespace.

## What's new

### `ufo-tech/dto-transformer`

This package now requires `ufo-tech/dto-transformer ^1.0` and builds on it:

- `Ufo\RpcObject\RPC\DTO extends Ufo\DTO\Attributes\AttrDTO` with a new optional `?string $transformerFQCN` argument.
- `Ufo\RpcObject\RPC\Assertions extends Ufo\DTO\Attributes\AttrAssertions`; `constructorArgs` became a constructor argument (`new Assertions($assertions, $constructorArgs)`), so the attribute is no longer patched through reflection.
- `Ufo\RpcObject\Rules\Validator\Validator extends Ufo\DTO\Helpers\Validator` and keeps only `throw()`.

**How to use**

```php
use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

final class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;
    // ...
}
```

### `#[RPC\Param]`

`Ufo\RpcObject\RPC\Param(int $type, null|string|int $default = null, ?string $convertorFQCN = null)` on a parameter or property declares which scalar types the value may arrive as. `$type` is a bit mask of `Param::STRING`, `FLOAT`, `INT`, `NULL` (shortcuts `NUMBER`, `MIXED`/`ANY`, `NULLABLE_STRING`, `NULLABLE_NUMBER`); other bits throw `InvalidArgumentException`. `getType()` returns the JSON Schema type or a list of types. The server uses it for parameter convertors (JsonRpcBundle 8.0).

```php
use Ufo\RpcObject\RPC;

public function find(
    #[RPC\Param(RPC\Param::NULLABLE_STRING)]
    ?\DateTimeImmutable $from = null,
): array { /* ... */ }
```

### `#[RPC\CacheRelation]`

Repeatable method attribute `CacheRelation(?string $serviceFQCN = null, array $methods = [], bool $warmUp = false)`: lists methods (of this or another service) whose cache depends on the current method. `cloneToClass()` copies it with another service class. The invalidation itself is done by the server (JsonRpcBundle 7.6).

```php
use Ufo\RpcObject\RPC;

#[RPC\CacheRelation(methods: ['get', 'list'], warmUp: true)]
public function update(int $id, string $name): bool { /* ... */ }
```

### `AttributeTransformer`

`Ufo\RpcObject\Transformer\AttributeTransformer` implements `Ufo\DTO\Interfaces\IDTOFromArrayTransformer`: `fromArray()` tries to rebuild one of `Cache`, `Lock`, `IgnoreApi`, `CacheRelation`, `DTO`, `ResultAsDTO` from an array and throws `NotSupportDTOException` otherwise. It lets a server restore attributes from a cached service map.

## Changes and fixes

- `RpcBatchRequest::addResult(array $result)` was replaced with `addResponse(RpcResponse $response, array $result)`; the response is also attached to the waiting request.
- `RpcResponse::getResult(bool $asIs = false)`: `true` returns the raw result without conversion.
- `RpcRequest` sorts named params by key (`ksort`) in the constructor; `getSpecialParams()` always contains `rayId` = the request id.
- `#[RPC\ResultAsDTO]` targets methods only; it gained `getRealFormat(string $paramName)`.
- Serializer attributes are imported from `Symfony\Component\Serializer\Attribute\…` instead of the deprecated `Annotation\…`.
- 3.0.1: `AttributeHelper::getMethodArgumentAttributesAsString()` finds `#[Assertions([...])]` when other attributes stand between it and the parameter, and with namespaced or nullable types; it returns `null` when nothing is found.

## Upgrade notes

1. **Add `ufo-tech/dto-transformer ^1.0`** (pulled in automatically) and update imports:

   | 2.9 | 3.0 |
   | --- | --- |
   | `Ufo\RpcObject\DTO\DTOTransformer` | `Ufo\DTO\DTOTransformer` |
   | `Ufo\RpcObject\DTO\IArrayConstructible` / `IArrayConvertible` | `Ufo\DTO\Interfaces\IArrayConstructible` / `IArrayConvertible` |
   | `Ufo\RpcObject\DTO\ArrayConstructibleTrait` / `ArrayConvertibleTrait` | `Ufo\DTO\ArrayConstructibleTrait` / `ArrayConvertibleTrait` |
   | `Ufo\RpcObject\Helpers\TypeHintResolver` | `Ufo\DTO\Helpers\TypeHintResolver` |

   `Ufo\RpcObject\DTO\DTOAttributesEnum` was removed from this package.
2. **`#[RPC\Response]` removed.** Use `#[RPC\ResultAsDTO]`.
3. **`Ufo\RpcObject\Events\*` removed** (`RpcEvent`, `BaseRpcEvent`, `RpcRequestEvent`, `RpcAsyncRequestEvent`, `RpcAsyncOutputEvent`, `RpcPreExecuteEvent`, `RpcPostExecuteEvent`, `RpcResponseEvent`, `RpcErrorEvent`). Early 3.0.0 commits still had them (with `RpcPreResponseEvent` / `RpcPostResponseEvent` replacing `RpcResponseEvent`); they were deleted before 3.0.1. JsonRpcBundle 8.0 defines its own events in `Ufo\JsonRpcBundle\EventDrivenModel\Events`.
4. `RpcBatchRequest::addResult()` → `addResponse(RpcResponse $response, array $result)`.
5. `#[RPC\ResultAsDTO]` on properties or parameters is no longer allowed: use `#[RPC\DTO]` there.
6. `Assertions::$constructorArgs` is a constructor argument now; code that set it through reflection must pass it to the constructor.
7. `Rules\Validator\Validator` no longer defines `validate()`, `getData()`, `getErrors()`, `hasErrors()`, `getCurrentError()` itself; they come from `Ufo\DTO\Helpers\Validator`. Re-check subclasses that overrode them.
