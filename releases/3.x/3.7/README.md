[Українська](README-UA.md)

# 3.7 — Readonly `RpcAsyncRequest` with `meta`, transport prefixes

Versions: 3.7.0. First released: 2026-09-17.

## Highlights

- `RpcAsyncRequest` is a `readonly` class with public `rpcRequest`, `token` and a new `meta` array.
- `RpcTransport::SYNC_PREFIX` (`'sync'`) and `RpcTransport::ASYNC_PREFIX` (`'rpc_async'`) constants.
- `AssociativeArrayDenormalizer` accepts `value_type` as a class name or a type object.

## What's new

### `RpcAsyncRequest`

```php
use Ufo\RpcObject\RpcAsyncRequest;

$message = new RpcAsyncRequest($rpcRequest, token: $token, meta: ['tenant' => 'acme']);

$message->rpcRequest; // RpcRequest
$message->meta;       // ['tenant' => 'acme']
```

`getRpcRequest()` still works but is deprecated and will be removed in 4.0.

### `RpcTransport` prefixes

`RpcTransport::SYNC_PREFIX` and `RpcTransport::ASYNC_PREFIX` name the DSN schemes used for sync and async transports, so consumers do not hard-code the strings.

### `value_type` in `AssociativeArrayDenormalizer`

The `value_type` context entry can be a class-name string or any object with `getClassName()` (Symfony PropertyInfo `Type`). Anything else throws `InvalidArgumentException`. Before 3.7 only a PropertyInfo `Type` object worked; the class no longer imports `Symfony\Component\PropertyInfo\Type`.

## Changes and fixes

None beyond the above.

## Upgrade notes

1. If you extend `RpcAsyncRequest`, the subclass must be `readonly`.
2. Replace `$asyncRequest->getRpcRequest()` with `$asyncRequest->rpcRequest`.
