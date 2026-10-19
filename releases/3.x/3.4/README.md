[Українська](README-UA.md)

# 3.4 — `$rpc.ignoreCache`, `RpcResponse::toArray()`, `rayId` kept across calls

Versions: 3.4.0, 3.4.1. First released: 2025-10-24.

## Highlights

- **`$rpc.ignoreCache`**: a new special parameter that asks the server to bypass the response cache.
- **`RpcResponse::toArray(ContextBuilderInterface)`**: normalize a whole response envelope with a given serializer context.
- **`rayId` is preserved**: `RpcRequest::getSpecialParams()` keeps an incoming `rayId` instead of replacing it with the current request id.

## What's new

### `$rpc.ignoreCache` (3.4.1)

`SpecialRpcParamsEnum::IGNORE_CACHE` (`'ignoreCache'`) and a public `SpecialRpcParams::$ignoreCache` (default `false`). The flag is part of `SpecialRpcParams::toArray()`. The cache itself is implemented by the server (json-rpc-bundle 9.1+ reads it).

**How to use**

```json
{"jsonrpc": "2.0", "id": 1, "method": "catalog.list", "params": {"$rpc": {"ignoreCache": true}}}
```

```php
$request->getRpcParams()?->ignoreCache; // true
```

### `RpcResponse::toArray()` (3.4.0)

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$array = $response->toArray(
    (new RpcResponseContextBuilder())->withResponseSignature($response)
);
```

The method normalizes the response with the package serializer and `$contextBuilder->toArray()` as context.

### `rayId` propagation (3.4.1)

`RpcRequest::getSpecialParams()` sets `rayId` to the request id only when it is not set yet (`??=`). `SpecialRpcParams::toArray()` now includes `rayId` when it is present, so a chain of calls keeps the root `rayId`.

## Changes and fixes

- `SpecialRpcParams` became a `readonly` class; `timeout` accepts `float|int`.
- `IRpcSpecialParamHandler` declares two more methods: `setParam(string $name, mixed $value): static` and `resetParams(): static`.
- `RpcRequest::fromArray()` no longer crashes on an undefined `$sp` when `ParamsSplitter::split()` throws and the request has no validation errors.
- `RealUrlValidator` (callback URL check): connection timeout 30 s (max duration stays 2 s), no forced `Content-Type` header, and no import of `Symfony\Component\HttpFoundation\Request` (that package was never a declared dependency).
- `SpecialParamsRules` builds constraints with named arguments (prepares for Symfony 8).

### Release history

3.4.0 was made on the `rc-3.4` branch on 2025-10-24, forked from 3.3.1, while `main` went on with 3.3.2 and 3.3.3. That means 3.4.0 still contains the 3.3.1 `DtoObjectNormalizer` which adds `$className` to every object (see [3.3](../3.3/README.md)). The branch was merged into `main` on 2025-12-17 as 3.4.1; the merge commit tagged 3.4.1 on Packagist is dated 2026-02-20.

## Upgrade notes

1. Prefer 3.4.1 over 3.4.0.
2. If you implement `IRpcSpecialParamHandler`, add `setParam()` and `resetParams()`.
3. If you extend `SpecialRpcParams`, your class must be `readonly` too.
4. If you relied on `rayId` being reset to the current request id in `getSpecialParams()`, note that an incoming `rayId` now wins.
