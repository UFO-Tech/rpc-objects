[Українська](README-UA.md)

# 2.0 — `#[RPC\Cache]`, parameter validator, Symfony 7

Versions: 2.0.0, 2.0.1. First released: 2024-05-10.

## Highlights

- `#[RPC\Cache]` marks a method's response as cacheable; `RpcResponse` carries the cache settings, `RpcCachedResponse` wraps a cached result.
- `Rules\Validator\RpcValidator` validates method arguments against `#[RPC\Assertions]` and throws `ConstraintsImposedException` with errors per parameter.
- Attributes are `final readonly` with public properties; `RPC\Assertions` and `RPC\AssertionsCollection` can be exported with `toArray()`.
- Serializer metadata is read from PHP attributes; `doctrine/annotations` is no longer required. `symfony/validator` `^7.0`.

## What's new

### `#[RPC\Cache]`

```php
use Ufo\RpcObject\RPC;

#[RPC\Cache(lifetimeSecond: RPC\Cache::T_10_MINUTES, environments: [RPC\Cache::ENV_PROD])]
public function list(): array { /* ... */ }
```

Defaults: `lifetimeSecond: Cache::T_MINUTE`, `environments: [Cache::ENV_PROD]`. Constants cover `T_MINUTE` … `T_YEAR`. `RpcResponse` got a new constructor argument `cache` and `getCacheInfo(): ?Cache`. The caching itself is done by the server.

### `RpcValidator`

```php
use Ufo\RpcObject\Rules\Validator\ConstraintsImposedException;
use Ufo\RpcObject\Rules\Validator\RpcValidator;

$validator = new RpcValidator($symfonyValidator);
try {
    $validator->validateMethodParams($procedure, 'create', $params);
} catch (ConstraintsImposedException $e) {
    $e->getConstraintsImposed(); // ["name" => ["This value should not be blank."]]
}
```

`ConstraintsImposedException` extends `RpcBadParamException`.

### `RPC\Response` with nullable properties

When `RPC\Response` builds a format from a DTO, nullable properties are prefixed with `?` (`?string`).

## Upgrade notes

- `RPC\Info` lost `getAlias()` / `getConcat()`: read the public properties `$alias` / `$concat`.
- `RPC\Assertions::$assertions` has no default value; `#[RPC\Assertions]` without arguments is no longer valid.
- `RPC\Info`, `RPC\Assertions`, `RPC\Cache` are `final`.
- `symfony/validator` `^7.0` (from 2.0.1; during 2.0.0 the constraint went from `*` to `^6.4`); `symfony/serializer`, `symfony/http-client`, `symfony/property-access` are no longer pinned to `6.*`.
- Serializer annotations (`@Groups` in docblocks) are no longer read; use PHP attributes.
