[Українська](README-UA.md)

# 2.7 — `DTOTransformer`: DTO ↔ array

Versions: 2.7.0–2.7.3. First released: 2025-01-13.

## Highlights

- New `Ufo\RpcObject\DTO\DTOTransformer` converts a DTO to an array and back by reflection.
- Interfaces `IArrayConvertible` / `IArrayConstructible` and traits `ArrayConvertibleTrait` / `ArrayConstructibleTrait` add `toArray()` / `fromArray()` to your DTOs.
- Callback URLs must have a top-level domain; `ext-intl` is now required.

## What's new

### `DTOTransformer`

- `DTOTransformer::toArray(object $dto)` reads every property; a value that implements `IArrayConvertible` is converted with its own `toArray()`.
- `DTOTransformer::fromArray(string $classFQCN, array $data)` creates the object without the constructor and fills its properties from `$data`. A missing key on a property without a default value throws `InvalidArgumentException("Missing required key: '<key>'")`.

**How to use**

```php
use Ufo\RpcObject\DTO\ArrayConstructibleTrait;
use Ufo\RpcObject\DTO\ArrayConvertibleTrait;
use Ufo\RpcObject\DTO\IArrayConstructible;
use Ufo\RpcObject\DTO\IArrayConvertible;

final class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public string $brand;
    public int $year;
}

$car = CarDTO::fromArray(['brand' => 'Volvo', 'year' => 2020]);
$car->toArray(); // ['brand' => 'Volvo', 'year' => 2020]
```

## Changes and fixes

- 2.7.1–2.7.2: `fromArray()` no longer throws for a key that is present with a `null` value. Side effect of the final check: a missing key is not reported either, the property is set to `null` (with an `Undefined array key` warning). The required-key check is restored in 2.8.
- 2.7.2: `CallbackRules` adds `requireTld: true` to every `Assert\Url` and to `AssertRealUrl`, so callbacks like `http://localhost/hook` are rejected (contributed in PR #1).
- 2.7.3: `AssertionsCollection` skips a parameter without `#[RPC\Assertions]` instead of failing on a missing attribute (PR #2). `ArrayConstructibleTrait::fromArray()` creates `static::class`, so it works in subclasses.
- 2.7.3: `ext-intl` added to `require`.

## Upgrade notes

- Install `ext-intl`.
- Callback URLs without a TLD (`localhost`, bare host names) now fail validation.
