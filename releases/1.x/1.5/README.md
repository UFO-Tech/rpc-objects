[Українська](README-UA.md)

# 1.5 — `RPC\*` attributes, `#[RPC\Assertions]`, dates in responses

Versions: 1.5. First released: 2024-04-19.

## Highlights

- Attributes moved to the `Ufo\RpcObject\RPC` namespace: `RPC\Info` (service alias), `RPC\Response` (former `RpcInfo`), `RPC\Assertions`.
- `#[RPC\Assertions([...])]` attaches Symfony constraints to a method parameter; `RPC\AssertionsCollection` collects them per parameter.
- `DateTimeInterface` values are normalized to strings by the default serializer.

## What's new

### `RPC\Info`

A class-level attribute with `alias` and `concat` (default `.`). The server uses it to build method names like `<alias><concat><method>`.

```php
use Ufo\RpcObject\RPC;

#[RPC\Info(alias: 'User')]
final class UserProcedure
{
    public function get(int $id): array { /* ... */ } // "User.get"
}
```

### `RPC\Response`

`Ufo\RpcObject\RpcInfo` was renamed to `Ufo\RpcObject\RPC\Response` (target: method). Arguments are unchanged: `responseFormat`, `dto`, `collection`.

### `RPC\Assertions`

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\RPC;

public function create(
    #[RPC\Assertions([new Assert\NotBlank(), new Assert\Length(max: 64)])]
    string $name,
): int { /* ... */ }
```

`RPC\Assertions` is a `readonly` class with a public `$assertions` array. `RPC\AssertionsCollection::addAssertions($key, $assertions)` / `getAssertionsCollection()` group them by parameter. Validation itself is done by the server.

### Dates

`Transformer::getDefault()` now registers `DateTimeNormalizer` before `ObjectNormalizer`, and `ObjectNormalizer` uses `ReflectionExtractor` for property types.

## Upgrade notes

- PHP `>=8.2` is required (was `>=8.1`).
- Replace `Ufo\RpcObject\RpcInfo` with `Ufo\RpcObject\RPC\Response`.
