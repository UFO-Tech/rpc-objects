[Українська](README-UA.md)

# 2.5 — `#[RPC\ResultAsDTO]`, response serialization helpers moved here

Versions: 2.5.0. First released: 2024-07-14.

## Highlights

- New `#[RPC\ResultAsDTO]` attribute describes a method result with a DTO class (optionally a collection). It replaces `#[RPC\Response]`, which is now deprecated.
- `RpcResponseContextBuilder` and `RpcErrorNormalizer` now live in `Ufo\RpcObject\Transformer` (previously the bundle's `Ufo\JsonRpcBundle\Serializer`).
- `TypeHintResolver::isRealClass()` tells a class name from a scalar type hint.

## What's new

### `#[RPC\ResultAsDTO]`

`Ufo\RpcObject\RPC\ResultAsDTO(string $dtoFQCN, bool $collection = false)` can be placed on a method or a property. The server reads it, parses the DTO and stores the format, which `getResponseFormat()` returns (it throws `RpcInternalException` while the DTO is not parsed yet).

**How to use**

```php
use Ufo\RpcObject\RPC;

#[RPC\ResultAsDTO(UserDTO::class)]
public function get(int $id): UserDTO { /* ... */ }

#[RPC\ResultAsDTO(UserDTO::class, collection: true)]
public function list(): array { /* ... */ }
```

### Serialization context and error normalizer

`Ufo\RpcObject\Transformer\RpcResponseContextBuilder` builds the Symfony Serializer context for `RpcResponse`: `withGroup()`, `withResponseSignature()` (adds the `result` or `error` group) and `removeParent()`. `Ufo\RpcObject\Transformer\RpcErrorNormalizer` normalizes `AbstractRpcErrorException` through `ExceptionToArrayTransformer` when the context contains `RpcErrorNormalizer::RPC_CONTEXT` (`rpc_handle`); the builder sets this key by default.

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$context = (new RpcResponseContextBuilder())->withGroup(['detail', 'list']);
```

## Changes and fixes

- `RpcResponse` imports the builder from `Ufo\RpcObject\Transformer` instead of `Ufo\JsonRpcBundle\Serializer`, so the package no longer depends on a bundle class.
- `TypeHintResolver::phpToJsonSchema()` now returns a string only: union types (`int|string` or an array of types) are no longer expanded into a list of `{type: …}` entries.

## Upgrade notes

- Replace `Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder` / `RpcErrorNormalizer` imports with `Ufo\RpcObject\Transformer\…`.
- `#[RPC\Response]` is deprecated and will be removed in 3.0: move to `#[RPC\ResultAsDTO]`.
- If you called `TypeHintResolver::phpToJsonSchema()` with union types, split the types yourself.
