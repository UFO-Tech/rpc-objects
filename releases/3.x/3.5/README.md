[Українська](README-UA.md)

# 3.5 — API versions in `#[RPC\Info]` and requests, deprecated options removed

Versions: 3.5.0. First released: 2026-02-22.

## Highlights

- **API versions**: `#[RPC\Info]` takes `version` and `removedMethods`; `RpcRequest` carries `apiVersion` (`v1` by default).
- **Cleanup**: `#[RPC\ResultAsDTO]` and the deprecated `RPC\DTO` / `RPC\Param` arguments were removed.
- This is the model side of json-rpc-bundle 10.0 (`/rpc/v2`, `removedMethods`).

## What's new

### `#[RPC\Info(version:, removedMethods:)]`

```php
use Ufo\RpcObject\RPC;

#[RPC\Info('user', version: 2, removedMethods: ['legacyLogin'])]
final class UserProcedureV2 extends UserProcedure {}
```

- `Info::$version` is a string: `'v' . $version` (`'v2'`). Default is `Info::DEFAULT_VERSION` = `'v1'`.
- `Info::$removedMethods` holds full method names: every entry is trimmed and prefixed with `alias . concat` (`'user.legacyLogin'`), duplicates are dropped.
- `alias` is now required (`string`, was `?string = null`).

### `apiVersion` in requests

`RpcRequest` has a new constructor argument and public readonly property `apiVersion` (`#[Ignore]`, not serialized). `RpcRequest::fromJson()` and `fromArray()` take it as a second argument:

```php
$request = RpcRequest::fromJson($body, apiVersion: 'v2');
$request->apiVersion; // 'v2'
```

### New helpers

- `RPC\DTO::getRealFormat(string $paramName)` and `RPC\DTO::isCollection()` (moved from the removed `ResultAsDTO`).
- `RPC\Param::bitFromType(string $type): int` maps `string` / `int` / `float` to `Param::STRING` / `INT` / `FLOAT`, anything else to `Param::NULL`.
- `RPC\Param::getData(string $contextKey): mixed` reads a value from the param context.

## Changes and fixes

- `RPC\Assertions` constructor documented (`@param Constraint[] $assertions`).
- `AttributeTransformer` no longer lists `ResultAsDTO`.

## Upgrade notes

This minor release contains breaking changes:

1. **`#[RPC\ResultAsDTO]` removed.** Use a return type or PHPDoc (`@return UserDto[]`), or `#[RPC\DTO]` / `#[RPC\DTOCollection]` on the method.
2. **`RPC\DTO`:** `renameKeys` and `transformerFQCN` arguments removed — pass `context: [DTO::C_RENAME_KEYS => […], DTO::C_TRANSFORMER => …]`. The constructor is now `(string $dtoFQCN, bool $collection = false, array $context = [])`.
3. **`RPC\Param`:** `default` and `convertorFQCN` arguments and public properties removed — pass `context: [Param::C_DEFAULT => …, Param::C_CONVERTOR => …]` and read them with `getData()`.
4. **`RPC\Info`:** `alias` is required; `#[RPC\Info]` without arguments no longer compiles.
5. Update json-rpc-bundle together with this package (the bundle 10.0 notes require matching `rpc-objects` and `dto-transformer` versions).
