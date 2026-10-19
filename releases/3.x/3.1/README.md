[Українська](README-UA.md)

# 3.1 — `ConstraintsImposedException` moved to rpc-exceptions, `mixed` result type

Versions: 3.1.0–3.1.3. First released: 2025-06-30.

## Highlights

- `ConstraintsImposedException` now comes from `ufo-tech/rpc-exceptions` (`Ufo\RpcError\ConstraintsImposedException`); the copy in this package was removed.
- `RpcResponse::getResult()` returns `mixed`.
- A short-lived documentation helper (`DocsHelper`) was added in 3.1.0 and removed again in 3.1.2.

## What's new

### `ConstraintsImposedException` from rpc-exceptions (3.1.1)

`Ufo\RpcObject\Rules\Validator\ConstraintsImposedException` was deleted. `RpcValidator` throws `Ufo\RpcError\ConstraintsImposedException` from `ufo-tech/rpc-exceptions` instead.

**How to use**

```php
use Ufo\RpcError\ConstraintsImposedException;

try {
    $rpcValidator->validateMethodParams($procedure, 'create', $params);
} catch (ConstraintsImposedException $e) {
    $errors = $e->getConstraintsImposed(); // [paramName => [messages]]
}
```

### `RpcResponse::getResult(): mixed` (3.1.3)

The return type was widened from `string|int|float|bool|array|object|null` to `mixed`. Behaviour did not change.

## Changes and fixes

- 3.1.0: added `Ufo\RpcObject\DocsHelper\UfoEnumsHelper` and `XUfoValuesEnum` (`x-ufo`, `x-ufo-enum`, `x-ufo-enum-name`, `x-ufo-assertions`, `x-method` keys for documentation).
- 3.1.2: `DocsHelper` was removed. The json-rpc-bundle notes for 8.x record the same rollback on the bundle side (literal `x-ufo…` keys are used instead of `XUfoValuesEnum`).

## Upgrade notes

1. Replace `use Ufo\RpcObject\Rules\Validator\ConstraintsImposedException;` with `use Ufo\RpcError\ConstraintsImposedException;`. `composer.json` still requires `ufo-tech/rpc-exceptions: *`, so make sure your lock file has a version that ships the class (json-rpc-bundle pairs this release with `ufo-tech/rpc-exceptions` ^1.3).
2. Do not rely on `Ufo\RpcObject\DocsHelper\*` — it exists only in 3.1.0–3.1.1.
3. If you override `RpcResponse::getResult()`, change its return type to `mixed`.
