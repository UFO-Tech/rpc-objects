[Українська](README-UA.md)

# 2.8 — `#[RPC\Lock]`, `$rpc` params object with `rayId`, validated and nested DTOs

Versions: 2.8.0–2.8.7. First released: 2025-03-13.

## Highlights

- `#[RPC\Lock]` describes a method lock (optionally per parameter value) on top of Symfony Lock.
- Special params moved into one `$rpc` object (`timeout`, `callback`, new `rayId`) described by `SpecialRpcParamsEnum`.
- `DTOTransformer` validates `#[RPC\Assertions]` on DTO properties, hydrates nested DTOs and collections with `#[RPC\DTO]`, supports constructors with `readonly` properties and key renaming.

## What's new

### `#[RPC\Lock]`

`Ufo\RpcObject\RPC\Lock(?string $paramName = null, ?int $ttl = null)` with constants `T_SECOND` … `T_30_SECOND`. `acquire(RpcRequest, LockFactory)` creates a lock with the key `md5(method + value of paramName)` and returns it, or `null` if it is held. The server decides when to call it (JsonRpcBundle 7.4).

```php
use Ufo\RpcObject\RPC;

#[RPC\Lock(paramName: 'orderId', ttl: RPC\Lock::T_10_SECOND)]
public function pay(string $orderId): bool { /* ... */ }
```

### `$rpc` special params and `rayId`

Special params are now read from a single `$rpc` key (`SpecialRpcParamsEnum::PREFIX`) instead of separate `$rpc.<name>` keys. `SpecialRpcParamsEnum` lists the cases `TIMEOUT` (`timeout`), `CALLBACK` (`callback`) and `PARENT_REQUEST` (`rayId`), holds `DEFAULT_TIMEOUT = 10` and builds `SpecialRpcParams` with `fromArray()` after validation. `SpecialRpcParams::getRayId()` returns the parent request id.

```json
{"jsonrpc": "2.0", "id": 1, "method": "Report.build",
 "params": {"id": 7, "$rpc": {"timeout": 30, "callback": "https://example.com/hook", "rayId": "parent-1"}}}
```

### DTOs with validation, nesting and constructors

- `#[RPC\Assertions]` can be placed on properties; `DTOTransformer::fromArray()` validates the value and throws `RpcBadParamException` (`<property><violation>`).
- New `#[RPC\DTO(dtoFQCN, collection)]` (and `ResultAsDTO`, which now extends it) on a property hydrates a nested DTO or a list of them. Attributes are processed by `DTOAttributesEnum`; unknown attributes are ignored.
- `fromArray(string $classFQCN, array $data, array $renameKey = [])` maps property names to other input keys.
- If the public constructor has `readonly` promoted properties, the object is created through the constructor (2.8.7).
- `toArray()` recursively converts nested objects and arrays.

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\DTO\DTOTransformer;
use Ufo\RpcObject\RPC;

final class CarDTO
{
    #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(min: 2, max: 50)])]
    public string $brand;

    #[RPC\DTO(EngineDTO::class)]
    public EngineDTO $engine;

    #[RPC\DTO(WheelDTO::class, collection: true)]
    public array $wheels = [];
}

$car = DTOTransformer::fromArray(CarDTO::class, $data, renameKey: ['brand' => 'make']);
```

In 2.8 `#[RPC\DTO]` targets methods and properties only; on promoted constructor parameters it is allowed from 2.9.

## Changes and fixes

- 2.8.0: `RpcRequest::getAllParams()` returns special params under `params['$rpc']`. Typed class constants (`const string …`) across the package. New `IRpcSpecialParamHandler` interface. Fallback request method name is `noname` instead of a random id.
- 2.8.1–2.8.2: recursive `toArray()`; `#[RPC\DTO]` added; `TypeHintResolver::normalizeArray()`.
- 2.8.3–2.8.5: `DTOAttributesEnum`, collections in `#[RPC\DTO]`, unknown attributes skipped.
- 2.8.6: requires `symfony/serializer ^7.2` and `symfony/validator ^7.2`; normalizer and validator signatures updated for Symfony 7.2 (nullable types).
- 2.8.7: constructor-based hydration for DTOs with `readonly` properties; a missing key takes the default value or throws `Missing required key` when there is none.

## Upgrade notes

- **Special params format changed.** Send `"$rpc": {"timeout": …, "callback": …}` instead of `"$rpc.timeout"` / `"$rpc.callback"`. `SpecialRpcParams::PREFIX`, `DEFAULT_TIMEOUT` and `SpecialRpcParams::fromArray()` were removed: use `SpecialRpcParamsEnum::PREFIX`, `::DEFAULT_TIMEOUT`, `::fromArray()`. `SpecialParamsRules::PARAMS` was removed.
- `#[RPC\Lock]` uses `symfony/lock`, which is not in this package's `require`; install it if you use the attribute.
- Symfony Serializer and Validator 7.2+ are required from 2.8.6.
