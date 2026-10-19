[Українська](README-UA.md)

# 3.8 — JSON-RPC notifications, `RpcRequestFactory`, Symfony 8

Versions: 3.8.2 (earlier 3.8 tags were not preserved). First released: 2026-09-25.

## Highlights

- **JSON-RPC notifications**: a message without the `id` key becomes `RpcNotificationRequest`; it serializes without `id`.
- **`RpcRequestFactory`**: one entry point that parses requests and picks the class; `RpcRequest::fromArray()` / `fromJson()` and batches delegate to it.
- **Symfony 8 support**: `symfony/serializer`, `symfony/validator` and the new explicit `symfony/property-info` accept `^7.2|^8.0`.
- **`$rpc` validation fixed**: special parameters are validated inside `params.$rpc`, not as top-level params.

## What's new

### Notifications

`Ufo\RpcObject\RpcNotificationRequest` extends `RpcRequest`. It gets an internal id from `RpcRequest::generateId()` (needed for batch queues), but `toArray()` and `refreshRawJson()` omit `id`. Only a missing `id` key makes a notification; `"id": null` or an invalid id stays an ordinary request with a validation error.

The package does not send responses. The consumer suppresses the response with `instanceof RpcNotificationRequest`, also inside batches (json-rpc-bundle 10.1 answers `204`).

### `RpcRequestFactory`

**How to use**

```php
use Ufo\RpcObject\Factory\RpcRequestFactory;
use Ufo\RpcObject\RpcNotificationRequest;

$request = RpcRequestFactory::fromArray([
    'jsonrpc' => '2.0', 'id' => 0, 'method' => 'users.find', 'params' => ['name' => 'Ada'],
]); // RpcRequest, id stays 0

$notification = RpcRequestFactory::fromJson('{"jsonrpc":"2.0","method":"users.refresh"}', apiVersion: 'v2');
$notification instanceof RpcNotificationRequest; // true
```

`fromArray(array $data, string $apiVersion = 'v1', ?string $classFQCN = null)`. A call through a subclass (`MyRequest::fromArray()`) keeps that subclass. An invalid message is still returned as an object with the error in `hasError()` / `getError()`; now this also covers exceptions thrown by the constructor, and the fallback fields are type-checked.

### Symfony 8 (3.8.x)

Requirements: `symfony/serializer` and `symfony/validator` `^7.2|^8.0`, new `symfony/property-info` `^7.2|^8.0`, `ufo-tech/rpc-exceptions` `^2.0.1` (was `*`), `ufo-tech/dto-transformer` `^3.1`.

### Special parameter validation (3.8.2)

`RequestRules::assertParams()` validates `params.$rpc` with `SpecialParamsRules::assertAllParamsCollection()`. Before, the `timeout` / `callback` rules were applied to top-level params with those names, so an ordinary `timeout` param could be rejected and `$rpc` itself was not checked. Also:

- `params: null` is rejected (before, `Type('array')` let `null` through); positional params (`["Ada", 42]`) stay valid.
- `$rpc` must be an array; unknown keys inside it are allowed.
- `timeout` must be `int|float` in `10..120`; a string `"30"` is rejected.
- `callback: null` is rejected (`NotNull` instead of `NotBlank`).

## Changes and fixes

- `RpcRequest::S_GROUP` changed from `'raw'` to the class name; `toArray()` and `refreshRawJson()` use `static::class` as the serialization group.
- An id of `0` (or `"0"`) is no longer replaced with a generated id; only `''` is.
- `RequestRules::assertAll(?string $classFqcn = null)` drops the `id` rule for notifications.
- `RpcBatchRequest::fromJson()` parses each entry with `RpcRequestFactory`.
- The package got a PHPUnit 11 test suite (`composer test`, `composer test:coverage`); `composer.json` no longer has a `version` field.

## Upgrade notes

1. If you serialized requests with the `'raw'` group, use `RpcRequest::S_GROUP` (now `RpcRequest::class`) or `static::class`.
2. Check code that treated `id: 0` as "no id".
3. Requests without `id` are now `RpcNotificationRequest`; do not send a response for them.
4. Update `ufo-tech/rpc-exceptions` to ^2.0.1 and `ufo-tech/dto-transformer` to ^3.1.
