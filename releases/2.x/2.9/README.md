[Українська](README-UA.md)

# 2.9 — Key renaming in `#[RPC\DTO]`, DTO attributes on constructor parameters

Versions: 2.9.0. First released: 2025-05-09.

## Highlights

- `#[RPC\DTO]` and `#[RPC\ResultAsDTO]` accept `renameKeys` and can be placed on parameters, including promoted constructor parameters.
- `IArrayConstructible::fromArray()` takes `$renameKey` and returns `static`.
- Clearer missing-key errors in `DTOTransformer::fromArray()`; a property can be skipped by renaming it to `null`.

## What's new

### `renameKeys` and parameter targets

`#[RPC\DTO(string $dtoFQCN, bool $collection = false, array $renameKeys = [])]`: the nested DTO is built with `DTOTransformer::fromArray($dtoFQCN, $value, $renameKeys)`. Both `DTO` and `ResultAsDTO` now target methods, properties and parameters.

**How to use**

```php
use Ufo\RpcObject\RPC;

final readonly class OrderDTO
{
    public function __construct(
        #[RPC\DTO(CustomerDTO::class, renameKeys: ['fullName' => 'name'])]
        public CustomerDTO $customer,
        #[RPC\DTO(ItemDTO::class, collection: true)]
        public array $items,
    ) {}
}
```

Here `CustomerDTO::$fullName` is filled from the input key `name`.

### `fromArray()` details

- `IArrayConstructible::fromArray(array $data, array $renameKey = []): static`, and `ArrayConstructibleTrait` passes `$renameKey` through.
- A rename to `null` (`['internalId' => null]`) skips that property or constructor parameter.
- A missing key throws `Missing required key for constructor param: '<key>'` or `… for property: '<key>'`. For a property, the default comes from its declaration or from the matching optional constructor parameter.

## Changes and fixes

- When a class has `readonly` properties, only the properties already passed to the constructor are skipped afterwards. Previously every remaining property was skipped, so non-constructor properties of such a class were never filled.

## Upgrade notes

- Classes that implement `IArrayConstructible` directly must update the signature to `fromArray(array $data, array $renameKey = []): static`.
- The text of missing-key exceptions changed; update tests or log filters that match it.
