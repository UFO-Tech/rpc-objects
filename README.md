# rpc-objects
Component for UFO-Tech RPC Library 🇺🇦

This package provides structured DTO handling for use in RPC-oriented systems, including:
* Attribute-based validation (Assertions)
* Recursive transformation into nested DTOs (DTO, ResultAsDTO)
* Conversion from/to arrays (IArrayConstructible, IArrayConvertible)
* Clean and extensible architecture compatible with Symfony Validator

### Installation

```bash
composer require ufo-tech/rpc-objects
```

### Release notes

All versions with what was added and how to use it: [releases](releases/README.md). Latest: [3.9](releases/3.x/3.9/README.md).

### Requests and notifications

Use `Ufo\RpcObject\Factory\RpcRequestFactory` to parse incoming messages:

```php
use Ufo\RpcObject\Factory\RpcRequestFactory;
use Ufo\RpcObject\RpcNotificationRequest;

$request = RpcRequestFactory::fromArray([
    'jsonrpc' => '2.0',
    'id' => 0,
    'method' => 'users.find',
    'params' => ['name' => 'Ada'],
]); // RpcRequest, id remains 0

$notification = RpcRequestFactory::fromJson(
    '{"jsonrpc":"2.0","method":"users.refresh"}',
    apiVersion: 'v2',
); // RpcNotificationRequest
```

Only the absence of the `id` key selects a notification. An explicit `null` or
invalid ID remains an ordinary request with a validation error under the existing
ID rules. Invalid messages retain their errors in `hasError()` / `getError()`.

`RpcRequest::fromArray()` and `RpcRequest::fromJson()` delegate construction to the
factory; parsing a batch uses the same selection for each entry. Calls made on an
explicit subclass retain that subclass.

Notifications keep an internal generated ID for queue bookkeeping, but `toArray()`
and `refreshRawJson()` omit the top-level `id` field. The request handler must use
`instanceof RpcNotificationRequest` to suppress outgoing responses, including in
batches; response dispatch remains the responsibility of the consuming application.

### Development and tests

Requires PHP >=8.3 and development dependencies:

```sh
composer install
composer test
composer test:coverage
```

The coverage command requires Xdebug and generates `build/coverage/index.html`.
See [tests/README.md](tests/README.md) for Docker commands, test scope, and known limitations.
