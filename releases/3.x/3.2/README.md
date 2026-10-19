[Українська](README-UA.md)

# 3.2 — dto-transformer 2, `#[RPC\DTOCollection]`, context options for attributes

Versions: 3.2.0. First released: 2025-09-29.

## Highlights

- Requires `ufo-tech/dto-transformer` ^2.0 (was ^1.0).
- New `#[RPC\DTOCollection(X::class)]` — a shortcut for a collection of DTOs.
- `#[RPC\DTO]` and `#[RPC\Param]` accept a `context` array; the old positional options are deprecated.

## What's new

### `#[RPC\DTOCollection]`

`Ufo\RpcObject\RPC\DTOCollection` extends `RPC\DTO` and always sets the collection flag. Targets: method, property, parameter.

**How to use**

```php
use Ufo\RpcObject\RPC;

final class OrderProcedure implements IRpcService
{
    public function import(
        #[RPC\DTOCollection(OrderLineDto::class)]
        array $lines,
    ): int { /* ... */ }
}
```

### `context` for `#[RPC\DTO]` and `#[RPC\Param]`

`RPC\DTO` options are stored in a context array with the keys from `Ufo\DTO\Attributes\AttrDTO`: `C_COLLECTION`, `C_RENAME_KEYS`, `C_TRANSFORMER`. The constructor arguments `collection`, `renameKeys` and `transformerFQCN` still work but are marked `@deprecated`.

`RPC\Param` got the same treatment: new constants `C_DEFAULT`, `C_CONVERTOR`, `C_COLLECTION`, a public `$context` property and `isCollection()`. The `default` and `convertorFQCN` arguments are `@deprecated`.

```php
#[RPC\DTO(UserDto::class, context: [
    RPC\DTO::C_COLLECTION => true,
    RPC\DTO::C_RENAME_KEYS => ['user_name' => 'name'],
])]
```

## Changes and fixes

- `AttributeTransformer::fromArray()` takes a 4th argument `array $namespaces = []` and passes it to `DTOTransformer::fromArray()` (dto-transformer 2 API).
- In `RPC\Param` the explicit `default` / `convertorFQCN` arguments are written after `context`, so passing those keys only through `context` has no effect — they are overwritten with `null`. Keep using the named arguments until 3.5, where they were removed.

## Upgrade notes

1. Update `ufo-tech/dto-transformer` to ^2.0 together with this package.
2. If you extend `AttributeTransformer`, add the `$namespaces` argument to `fromArray()`.
3. Start moving `renameKeys:` / `transformerFQCN:` / `collection: true` to `context:` or `#[RPC\DTOCollection]`; the arguments are removed in [3.5](../3.5/README.md).
