[Українська](README-UA.md)

# 3.3 — Enum and DTO normalizers, `Param` collections, `ResultAsDTO` deprecated

Versions: 3.3.0–3.3.3. First released: 2025-09-30.

## Highlights

- **Enums in the serializer**: new `EnumNormalizer` turns enums into their value (or name) and back.
- **`DtoObjectNormalizer`** wraps Symfony's `ObjectNormalizer`: objects implementing `IArrayConvertible` are normalized via `toArray()`, denormalization goes through `DTOTransformer`.
- `#[RPC\Param]` got a `collection` argument; `#[RPC\ResultAsDTO]` is deprecated in favour of the return type / PHPDoc.

## What's new

### `EnumNormalizer`

`Ufo\RpcObject\Transformer\EnumNormalizer` is registered first in `Transformer::getDefault()`. A `BackedEnum` is normalized to its `value`, a pure `UnitEnum` to its `name`. Denormalization accepts a string, an int or an enum instance; an invalid value becomes a Symfony `UnexpectedValueException`.

```php
use Ufo\RpcObject\Transformer\Transformer;

enum Status: string { case Active = 'active'; }

Transformer::getDefault()->normalize(Status::Active);                 // 'active'
Transformer::getDefault()->denormalize('active', Status::class);     // Status::Active
```

### `DtoObjectNormalizer`

`Ufo\RpcObject\Transformer\DtoObjectNormalizer` replaces the plain `ObjectNormalizer` in the default serializer (and inside `ConstraintObjectNormalizer` / `AssociativeArrayDenormalizer`):

- `IArrayConvertible` objects → `$object->toArray()`;
- other objects → `ObjectNormalizer`, plus the `$className` key (short class name) **only** when the context has `DtoObjectNormalizer::AS_SMART => true` (since 3.3.3; see below);
- denormalization → `DTOTransformer::fromArray($type, $data, namespaces: $context['namespaces'] ?? [])`.

```php
use Ufo\RpcObject\Transformer\DtoObjectNormalizer;

$data = Transformer::getDefault()->normalize($user, context: [DtoObjectNormalizer::AS_SMART => true]);
// ['name' => 'Ada', '$className' => 'UserDto']
```

### `#[RPC\Param(collection: …)]`

`RPC\Param` takes `bool $collection = false` as the **second** constructor argument (before the deprecated `default` / `convertorFQCN`). `#[RPC\DTOCollection]` now calls the parent with `collection: true` directly, and the `collection` argument of `RPC\DTO` is no longer deprecated.

### `#[RPC\ResultAsDTO]` deprecated

Marked `#[Deprecated('Use docblock or return typehint')]`. It was removed in [3.5](../3.5/README.md).

## Changes and fixes

- 3.3.0: `RpcResponse::getResult()` can return a string or int for an object result (`normalizeResult(): array|string|int`), e.g. an enum.
- 3.3.0: the `#[RPC\Assertions]` source parser (`AttributeHelper`) no longer matches a parameter whose name is a prefix of another (`$id` vs `$idList`).
- 3.3.1: normalizer order fixed — `DateTimeNormalizer` and `UidNormalizer` run before `DtoObjectNormalizer` again. In 3.3.0 dates and UUIDs were normalized as plain objects.
- 3.3.2: the `$className` key is no longer added to every normalized object; it became opt-in via context (`AS_SIMPLE_ARRAY`).
- 3.3.3: the context key was renamed to `DtoObjectNormalizer::AS_SMART` (`'asSmart'`).
- 3.4.0 was published from a separate `rc-3.4` branch between 3.3.1 and 3.3.2 (see [3.4](../3.4/README.md)); 3.3.2 and 3.3.3 continued on `main`.

## Upgrade notes

1. **Avoid 3.3.0–3.3.1** if you serialize objects: they add `$className` to every object in the response, and 3.3.0 also breaks `DateTime` / UUID output. Use 3.3.3 or later.
2. `RPC\Param` positional arguments shifted: `new Param(Param::STRING, 'x')` now passes `'x'` as `collection`. Use named arguments (`default:`, `convertorFQCN:`).
3. In `RPC\DTO` the explicit `collection` / `renameKeys` / `transformerFQCN` arguments are now written **after** `context`, so keys given only through `context` are overwritten by the defaults (this also affects `RPC\Param` since 3.2). Use the named arguments until [3.5](../3.5/README.md).
4. If you referenced `DtoObjectNormalizer::AS_SIMPLE_ARRAY` (3.3.2), switch to `AS_SMART`.
