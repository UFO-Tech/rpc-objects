[Українська](README-UA.md)

# 3.10 — dto-transformer 4, request validation error codes

Version: 3.10.0. Released: 2026-10-30.

## Highlights

- Requires `ufo-tech/dto-transformer` ^4.0 (was ^3.1) and uses its 4.0 class names.
- `RpcRequestFactory` reports request validation errors with the JSON-RPC 2.0 code that matches the field: `-32600` (Invalid Request) for `id`, `method`, `jsonrpc`, `-32602` (Invalid params) for `params`. Before, every validation error was `-32602`.
- `Rules\Validator\Validator::throw()` now throws the exception class it is given.
- `RpcResponse::getResult()` normalizes objects at any depth of an array result, not only at the first level.

## What's new

### dto-transformer 4.0 names

The package now refers to dto-transformer through its 4.0 names:

| 3.x name | 4.0 name |
|---|---|
| `Ufo\DTO\Interfaces\IArrayConstructible` | `Ufo\DTO\Contract\ArrayConstructibleInterface` |
| `Ufo\DTO\Interfaces\IArrayConvertible` | `Ufo\DTO\Contract\ArrayConvertibleInterface` |
| `Ufo\DTO\Interfaces\IDTOFromArrayTransformer` | `Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface` |
| `Ufo\DTO\Helpers\TypeHintResolver` | `Ufo\DTO\Schema\Type\TypeHintResolver` |

`AttributeTransformer` implements `StaticDTOFromArrayTransformerInterface`; `DtoObjectNormalizer` checks `ArrayConvertibleInterface`. dto-transformer 4.0 keeps the 3.x names as aliases of the same types, so your DTOs on the old interfaces still pass these checks.

### Error codes in `RpcRequestFactory`

`RpcRequestFactory::fromArray()` / `fromJson()` validate the whole request in one pass. The error class is now chosen by the first violation: a violation under `[params]` gives `RpcBadParamException` (`-32602`), any other field gives `RpcBadRequestException` (`-32600`). The choice is made in the new protected `RpcRequestFactory::validationErrorClass()`.

```php
use Ufo\RpcObject\Factory\RpcRequestFactory;

RpcRequestFactory::fromArray(['id' => 1, 'params' => ['a' => 1]])->getError()->getCode();              // -32600, [method] missing
RpcRequestFactory::fromArray(['id' => 1, 'method' => 'ping', 'params' => []])->getError()->getCode(); // -32602, [params] empty
```

Errors in `params.$rpc` (`callback`, `timeout`, …) stay `-32602`.

## Changes and fixes

- `Rules\Validator\Validator::throw(string $class)` compared the class name string with `instanceof`, so the check was always false and `RpcBadParamException` was thrown whatever was passed. It now uses `is_a($class, AbstractRpcErrorException::class, true)`: a subclass of `AbstractRpcErrorException` is thrown as given, any other class still falls back to `RpcBadParamException`. This is why `RpcRequestFactory` never produced `-32600` before.
- `AttributeTransformer::isSupportClass()` compares the class name with `ArrayConstructibleInterface::class`.
- `RpcResponse::getResult()` normalized only the objects lying directly in an array result: a DTO inside a list of lists or inside a map of lists came back as an object. The array is now walked recursively by the new protected `RpcResponse::normalizeArray()`, so `[[$dto], [$dto, $dto]]` and `['left' => [$dto], 'right' => [[$dto]]]` are normalized in full; scalars and values the serializer cannot normalize are returned as they are.

## Upgrade notes

1. **dto-transformer 4.0 is required.** If your code imports the 3.x names, move to the 4.0 names (table above); the aliases are removed in dto-transformer 4.1.
2. **Error codes changed for invalid requests.** A missing or invalid `id`, `method` or `jsonrpc` is now reported as `-32600` instead of `-32602`. Clients and tests that check the code of such errors must expect `-32600`. Errors in `params` (including `$rpc`) keep `-32602`.
3. **`Validator::throw()` honours its argument.** If you pass an `AbstractRpcErrorException` subclass, that class is thrown now (before it was always `RpcBadParamException`).
4. **`AttributeTransformer::isSupportClass()` matches only the 4.0 interface name.** A parameter type written as `IArrayConstructible` (the reflected name is the one written in code) is no longer recognised — use `ArrayConstructibleInterface`.
