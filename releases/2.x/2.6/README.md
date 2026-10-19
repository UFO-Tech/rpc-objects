[Українська](README-UA.md)

# 2.6 — `#[RPC\IgnoreApi]`, associative-array denormalizer, stricter callback URL check

Versions: 2.6.0–2.6.4. First released: 2024-07-24.

## Highlights

- New `#[RPC\IgnoreApi]` attribute: the server can skip a public method when it builds the API.
- The default serializer reads PHPDoc and constructor types and denormalizes associative arrays of DTOs (`array<string, Dto>`).
- `AssertRealUrl` checks a callback URL with `OPTIONS`/`POST` and caches the result per URL.

## What's new

### `#[RPC\IgnoreApi]`

`Ufo\RpcObject\RPC\IgnoreApi` is an empty marker attribute for methods. The package only defines it; the RPC server decides what to do with it (JsonRpcBundle 7.2 hides such methods from the API and the docs).

**How to use**

```php
use Ufo\RpcObject\RPC;

#[RPC\IgnoreApi]
public function setFormatter(Formatter $f): void { /* not an RPC method */ }
```

### Serializer: type extractors and associative arrays

`Transformer::getDefault()` builds `ObjectNormalizer` with a `PropertyInfoExtractor` (`ConstructorExtractor` + `PhpDocExtractor`, `ReflectionExtractor`, `SerializerExtractor`) instead of a bare `ReflectionExtractor`, so `@var Dto[]` and constructor PHPDoc are taken into account. The new `Ufo\RpcObject\Transformer\AssociativeArrayDenormalizer` handles arrays with non-sequential keys: when the context carries a `value_type`, each array item is denormalized into that class, other values are kept as is.

Final normalizer order (2.6.3): `DateTimeNormalizer`, `UidNormalizer`, `ConstraintObjectNormalizer`, `ObjectNormalizer`, `AssociativeArrayDenormalizer`, `ArrayDenormalizer`. The JSON encoder is registered under the `json` key.

## Changes and fixes

- 2.6.1: type extractors added to the default serializer, normalizer order changed.
- 2.6.2: `AssociativeArrayDenormalizer` added (values copied as is).
- 2.6.3: `AssociativeArrayDenormalizer` receives the object normalizer and denormalizes nested DTOs by `value_type`; `DateTimeNormalizer` and `UidNormalizer` moved to the front of the chain.
- 2.6.4: `RealUrlValidator` sends `OPTIONS` (2 s timeout) and accepts the URL when `Allow` contains `POST`; otherwise it sends a `POST` with `{"rpc.callback": "check real webhook url"}` and accepts any status below 400. Results are cached in a static array for the process lifetime. Empty values are skipped before the URL format check.
- 2.6.4: when the constructor fails, `RpcRequest::fromArray()` builds a fallback object without it; that object now gets `params` too (previously the property stayed uninitialized).

## Upgrade notes

- `RealUrlValidator` uses `Symfony\Component\HttpFoundation\Request` constants, but `symfony/http-foundation` is not in this package's `require`. Install it if your application does not have it already.
- Callback endpoints are now probed with `OPTIONS` and possibly a test `POST`; make sure the endpoint tolerates that request.
